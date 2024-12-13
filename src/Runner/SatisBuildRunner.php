<?php

namespace Playbloom\Runner;

use Playbloom\Event\BuildEvent;
use Playbloom\Process\ProcessFactory;
use Playbloom\Service\Manager;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;
use Symfony\Component\Process\Exception\RuntimeException;

class SatisBuildRunner
{
    protected string $satisFilename;

    protected ProcessFactory $processFactory;

    protected int $timeout = 600;

    protected LockInterface $lock;

    protected Manager $manager;

    public function __construct(string $satisFilename, LockFactory $buildLockFactory, ProcessFactory $processFactory, Manager $manager)
    {
        $this->satisFilename = $satisFilename;
        $this->lock = $buildLockFactory->createLock('build');
        $this->processFactory = $processFactory;
        $this->manager = $manager;
    }

    public function setProcessFactory(ProcessFactory $processFactory): self
    {
        $this->processFactory = $processFactory;

        return $this;
    }

    /**
     * @return \Generator|string[]
     */
    public function run(): \Generator
    {
        $this->lock->acquire(true);
        try {
            $process = $this->processFactory->create($this->getCommandLine(), $this->timeout);
            $process->start();

            yield $process->getCommandLine();

            foreach ($process as $line) {
                $line = $this->trimLine($line);
                if (empty($line)) {
                    continue;
                }
                yield $line;
            }

            yield $process->getExitCodeText();
        } finally {
            $this->lock->release();
        }
    }

    public function onBuild(BuildEvent $event): void
    {
        $repository = $event->getRepository();
        $command = $this->getCommandLine($repository ? $repository->getUrl() : null);
        $process = $this->processFactory->create($command, $this->timeout);

        try {
            $this->lock->acquire(true);
            $status = $process->run();
        } catch (RuntimeException $exception) {
            $status = 1;
        } finally {
            $this->lock->release();
        }

        $event->setStatus($status);
    }

    /**
     * @return string[]
     */
    protected function getCommandLine(?string $repositoryUrl = null): array
    {
        $command = ['bin/satis', 'build'];
        $configuration = $this->manager->getConfig();
        $outputDir = $configuration->getOutputDir();
        array_push($command, $this->satisFilename, $outputDir, '--skip-errors', '--no-ansi', '--verbose');
        if (!empty($repositoryUrl)) {
            $command[] = sprintf('--repository-url=%s', $repositoryUrl);
            // keep it while satis fails to build with one repo dependencies
            // https://github.com/composer/satis/issues/493
            $command[] = '--repository-strict';
        }

        return $command;
    }

    protected function trimLine(string $line): string
    {
        return trim($line, " \t\n\r\0\x0B\x08");
    }
}
