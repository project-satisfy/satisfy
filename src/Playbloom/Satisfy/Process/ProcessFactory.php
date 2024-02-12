<?php

namespace Playbloom\Satisfy\Process;

use Symfony\Component\Process\Process;

class ProcessFactory
{
    protected string $rootPath;

    protected string $composerHome;

    public function __construct(string $rootPath, string $composerHome)
    {
        $this->rootPath = $rootPath;
        $this->composerHome = $composerHome;
    }

    public function getRootPath(): string
    {
        return $this->rootPath;
    }

    public function create(array $command, ?int $timeout = null): Process
    {
        $exec = reset($command);
        $command[key($command)] = $this->rootPath . '/' . $exec;

        return new Process($command, $this->rootPath, $this->getEnv(), null, $timeout);
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

        if (empty($env['COMPOSER_HOME'])) {
            $env['COMPOSER_HOME'] = $this->composerHome;
        }
        $env['COMPOSER_NO_INTERACTION'] = 1;

        return $env;
    }
}
