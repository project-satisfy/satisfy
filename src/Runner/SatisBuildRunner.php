<?php

namespace Playbloom\Runner;

use Playbloom\Event\BuildFinishedEvent;
use Playbloom\Event\BuildStartingEvent;
use Playbloom\Model\BuildRequest;
use Playbloom\Model\BuildResult;
use Playbloom\Process\ProcessFactory;
use Playbloom\Service\Manager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;
use Symfony\Component\Process\Exception\RuntimeException;

/**
 * Runs Satis. Only the worker calls this; web requests queue builds instead.
 */
class SatisBuildRunner
{
    protected LockInterface $lock;

    public function __construct(
        protected string $satisFilename,
        LockFactory $buildLockFactory,
        protected ProcessFactory $processFactory,
        protected Manager $manager,
        protected EventDispatcherInterface $dispatcher,
        protected int $timeout = 600,
        protected ?string $logFile = null,
    ) {
        $this->lock = $buildLockFactory->createLock('build');
    }

    public function setProcessFactory(ProcessFactory $processFactory): self
    {
        $this->processFactory = $processFactory;

        return $this;
    }

    /**
     * Runs one build under the build lock, writing its output to the build log.
     *
     * @param callable(string): void|null $onOutput receives Satis's output as it arrives
     */
    public function build(BuildRequest $request, ?callable $onOutput = null): BuildResult
    {
        $started = microtime(true);
        $this->lock->acquire(true);
        try {
            $starting = new BuildStartingEvent($request);
            $this->dispatcher->dispatch($starting);
            if ($starting->isCancelled()) {
                $reason = (string) $starting->getCancelReason();
                $this->writeLog(sprintf("Build cancelled: %s\n", $reason), true);

                return BuildResult::cancelled($reason, microtime(true) - $started);
            }

            $process = $this->processFactory->create($this->getCommandLine($request), $this->timeout);
            $this->writeLog('$ ' . $process->getCommandLine() . "\n", true);
            try {
                $exitCode = $process->run(function (string $type, string $buffer) use ($onOutput): void {
                    $this->writeLog($buffer);
                    if (null !== $onOutput) {
                        $onOutput($buffer);
                    }
                });
            } catch (RuntimeException $exception) {
                $this->writeLog($exception->getMessage() . "\n");
                $exitCode = 1;
            }
            $this->writeLog(sprintf("\nExit code %d\n", $exitCode));

            $this->dispatcher->dispatch(new BuildFinishedEvent($request, $exitCode));
        } finally {
            $this->lock->release();
        }

        return new BuildResult($exitCode, microtime(true) - $started);
    }

    /**
     * The end of the last build's log, at most $maxBytes long.
     */
    public function getLastLog(int $maxBytes = 65536): string
    {
        if (null === $this->logFile || !is_readable($this->logFile)) {
            return '';
        }
        $size = (int) filesize($this->logFile);

        return (string) file_get_contents($this->logFile, false, null, max(0, $size - $maxBytes));
    }

    /**
     * When the last build's log was last written, as a Unix timestamp.
     */
    public function getLastLogTime(): ?int
    {
        if (null === $this->logFile || !is_readable($this->logFile)) {
            return null;
        }

        return filemtime($this->logFile) ?: null;
    }

    /**
     * @return string[]
     */
    public function getCommandLine(BuildRequest $request): array
    {
        $command = ['bin/satis', 'build'];
        $outputDir = $this->manager->reload()->getOutputDir();
        array_push($command, $this->satisFilename, $outputDir, '--skip-errors', '--no-ansi', '--verbose');
        $repositoryUrls = $request->getRepositoryUrls();
        if (!empty($repositoryUrls)) {
            foreach ($repositoryUrls as $repositoryUrl) {
                $command[] = sprintf('--repository-url=%s', $repositoryUrl);
            }
            // keep it while satis fails to build with one repo dependencies
            // https://github.com/composer/satis/issues/493
            $command[] = '--repository-strict';
        }

        return $command;
    }

    protected function writeLog(string $text, bool $truncate = false): void
    {
        if (null === $this->logFile) {
            return;
        }
        if (!is_dir(dirname($this->logFile))) {
            @mkdir(dirname($this->logFile), 0775, true);
        }
        @file_put_contents($this->logFile, $text, $truncate ? 0 : FILE_APPEND);
    }
}
