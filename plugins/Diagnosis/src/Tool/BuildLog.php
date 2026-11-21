<?php

namespace Playbloom\Plugin\Diagnosis\Tool;

use Symfony\AI\Agent\Toolbox\Attribute\AsTool;

/**
 * The redacted log of the failed build, for the agent to search and read
 * beyond the end it is given.
 */
#[AsTool('log_search', 'Lines of the failed build\'s log that match a regular expression, case-insensitively, each with its line number. At most 40 lines.', method: 'search')]
#[AsTool('log_lines', 'Lines of the failed build\'s log by number, to read around a match. At most 80 lines.', method: 'lines')]
final class BuildLog
{
    private const MAX_MATCHES = 40;
    private const MAX_LINES = 80;

    /** @var list<string>|null */
    private ?array $lines = null;

    public function __construct(private readonly string $path)
    {
    }

    /**
     * @param string $pattern a regular expression without delimiters, such as "error|failed"
     */
    public function search(string $pattern): string
    {
        $regex = '~' . str_replace('~', '\~', $pattern) . '~i';
        if (false === @preg_match($regex, '')) {
            return 'That is not a valid regular expression.';
        }

        $found = [];
        foreach ($this->read() as $index => $line) {
            if (1 === preg_match($regex, $line)) {
                $found[] = sprintf('%d: %s', $index + 1, $line);
                if (self::MAX_MATCHES === count($found)) {
                    break;
                }
            }
        }

        return [] === $found ? 'No line matches.' : implode("\n", $found);
    }

    /**
     * @param int $from  the number of the first line, counting from 1
     * @param int $count how many lines to return, at most 80
     */
    public function lines(int $from, int $count = 40): string
    {
        $lines = $this->read();
        $from = max(1, $from);
        $slice = array_slice($lines, $from - 1, max(1, min($count, self::MAX_LINES)), true);
        if ([] === $slice) {
            return sprintf('The log has %d lines.', count($lines));
        }

        return implode("\n", array_map(static fn (int $index, string $line): string => sprintf('%d: %s', $index + 1, $line), array_keys($slice), $slice));
    }

    /**
     * @return list<string>
     */
    private function read(): array
    {
        return $this->lines ??= file($this->path, \FILE_IGNORE_NEW_LINES) ?: [];
    }
}
