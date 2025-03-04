<?php

namespace Playbloom\Satisfy\Model;

class BuildContext
{
    private int $exitCode;
    private string $command;
    private string $output;
    private string $errorOutput;
    private ?\Throwable $throwable;

    public function __construct(int $exitCode, string $command, string $output, string $errorOutput, ?\Throwable $throwable = null)
    {
        $this->exitCode = $exitCode;
        $this->command = $command;
        $this->output = $output;
        $this->errorOutput = $errorOutput;
        $this->throwable = $throwable;
    }

    public function getExitCode(): int
    {
        return $this->exitCode;
    }

    public function getCommand(): string
    {
        return $this->command;
    }

    public function getOutput(): string
    {
        return $this->output;
    }

    public function getErrorOutput(): string
    {
        return $this->errorOutput;
    }

    public function getThrowable(): ?\Throwable
    {
        return $this->throwable;
    }
}
