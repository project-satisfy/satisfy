<?php

namespace Playbloom\Satisfy\Runner;

use Symfony\Component\Process\Process;

class SatisBuildRunner
{
    /** @var string */
    protected $rootPath;

    /** @var string */
    protected $satisFilename;

    /** @var string */
    protected $homePath;

    /** @var int */
    protected $timeout = 600;

    public function __construct(string $rootPath, string $satisFilename, string $homePath)
    {
        $this->rootPath = $rootPath;
        $this->satisFilename = $satisFilename;
        $this->homePath = $homePath;
    }

    /**
     * @return \Generator|string[]
     */
    public function run(): \Generator
    {
        $process = new Process($this->getCommandLine(), $this->rootPath, $this->getEnv(), null, $this->timeout);
        $process->start();

        yield $process->getCommandLine();

        foreach ($process as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }
            yield $line;
        }

        yield $process->getExitCodeText();
    }

    protected function getCommandLine(): string
    {
        $line = $this->rootPath . '/bin/satis build';
        $line .= ' --skip-errors --no-ansi --no-interaction --verbose';

        return $line;
    }

    protected function getEnv(): array
    {
        $env = [];

        foreach ($_SERVER as $k => $v) {
            if (is_string($v) && false !== $v = getenv($k)) {
                $env[$k] = $v;
            }
        }

        foreach ($_ENV as $k => $v) {
            if (is_string($v)) {
                $env[$k] = $v;
            }
        }

        if (empty($env['HOME'])) {
            $env['HOME'] = $this->homePath;
        }

        return $env;
    }
}
