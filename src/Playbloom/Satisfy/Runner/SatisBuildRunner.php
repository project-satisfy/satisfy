<?php

namespace Playbloom\Satisfy\Runner;

use Playbloom\Satisfy\Event\BuildEvent;
use Playbloom\Satisfy\Process\ProcessFactory;
use Symfony\Component\Process\Exception\RuntimeException;

class SatisBuildRunner
{
    /** @var string */
    protected $satisFilename;

    /** @var ProcessFactory */
    protected $processFactory;

    /** @var int */
    protected $timeout = 600;

    public function __construct(string $satisFilename)
    {
        $this->satisFilename = $satisFilename;
    }

    public function setProcessFactory(ProcessFactory $processFactory)
    {
        $this->processFactory = $processFactory;

        return $this;
    }

    /**
     * @return \Generator|string[]
     */
    public function run(): \Generator
    {
        $process = $this->processFactory->create($this->getCommandLine());
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
    }

    public function onBuild(BuildEvent $event)
    {
        $repository = $event->getRepository();
        $command = $this->getCommandLine($repository ? $repository->getUrl() : null);
        $process = $this->processFactory->create($command, $this->timeout);
        $process->disableOutput();

        try {
            $status = $process->run();
        } catch (RuntimeException $exception) {
            $status = 1;
        }

        $event->setStatus($status);
    }

    protected function getCommandLine(string $repositoryUrl = null): string
    {
        $line = $this->processFactory->getRootPath() . '/bin/satis build';
        $line .= ' --skip-errors --no-ansi --verbose';
        if (!empty($repositoryUrl)) {
            $line .= sprintf(' --repository-url="%s"', $repositoryUrl);
            // keep it while satis fails to build with one repo dependencies
            // https://github.com/composer/satis/issues/493
            $line .= ' --repository-strict';
        }

        return $line;
    }

    protected function trimLine(string $line): string
    {
        return trim($line, " \t\n\r\0\x0B\x08");
    }
}
