<?php

namespace Playbloom\Satisfy\Process;

use Symfony\Component\Process\Process;

class ProcessFactory
{
    /** @var string */
    protected $rootPath;

    /** @var string */
    protected $homePath;

    public function __construct(string $rootPath, string $homePath)
    {
        $this->rootPath = $rootPath;
        $this->homePath = $homePath;
    }

    public function getRootPath(): string
    {
        return $this->rootPath;
    }

    public function create(string $command, int $timeout = null): Process
    {
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

        if (empty($env['HOME'])) {
            $env['HOME'] = $this->homePath;
        }

        return $env;
    }
}
