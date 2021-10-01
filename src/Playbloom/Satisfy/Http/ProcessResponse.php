<?php

namespace Playbloom\Satisfy\Http;

use Symfony\Component\HttpFoundation\StreamedResponse;

class ProcessResponse extends StreamedResponse
{
    public static function createFromOutput(\Iterator $output): self
    {
        ini_set('implicit_flush', '1');
        ob_implicit_flush(PHP_MAJOR_VERSION >= 8 ? true : 1);

        $callback = function () use ($output) {
            foreach ($output as $line) {
                self::outputLine($line);
            }
            self::outputLine('__done__');
        };

        return new self($callback, self::HTTP_OK, ['Content-Type' => 'text/event-stream']);
    }

    protected static function outputLine(string $line)
    {
        echo 'data: ', $line, PHP_EOL, PHP_EOL;
    }
}
