<?php

namespace Playbloom\Satisfy\Runner;

use Playbloom\Satisfy\Event\BuildEvent;
use Playbloom\Satisfy\Process\ProcessFactory;
use Playbloom\Satisfy\Service\Manager;
use Symfony\Component\Lock\Lock;
use Symfony\Component\Process\Exception\RuntimeException;

class SatisBuildRunner
{
    /** @var string */
    protected $satisFilename;

    /** @var ProcessFactory */
    protected $processFactory;

    /** @var int */
    protected $timeout = 600;

    /** @var Lock */
    protected $lock;

    /** @var Manager */
    protected $manager;

    public function __construct(string $satisFilename, Lock $lock, ProcessFactory $processFactory, Manager $manager)
    {
        $this->satisFilename = $satisFilename;
        $this->lock = $lock;
        $this->processFactory = $processFactory;
        $this->manager = $manager;
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

    public function onBuild(BuildEvent $event)
    {
        $repository = $event->getRepository();
        $command = $this->getCommandLine($repository ? $repository->getUrl() : null);
        $process = $this->processFactory->create($command, $this->timeout);
        $process->disableOutput();

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
