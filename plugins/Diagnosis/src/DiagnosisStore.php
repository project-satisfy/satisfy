<?php

namespace Playbloom\Plugin\Diagnosis;

use Playbloom\Model\BuildRequest;
use Playbloom\Service\LogRedactor;

/**
 * One directory per failed build under the diagnosis directory: build.json,
 * what was built and how it ended; build.log, its log with credentials
 * redacted; diagnosis.json, the model's answer; diagnose.log, the output of
 * the process that asked. last.json, beside them, is the newest diagnosis.
 */
final class DiagnosisStore
{
    private const KEEP = 20;
    private const ID = '/^\d{8}-\d{6}-[0-9a-f]{6}$/';

    public function __construct(private readonly DiagnosisConfig $config)
    {
    }

    /**
     * Keeps a redacted copy of a failed build's log, before the next build
     * replaces it, and returns the id it is kept under.
     */
    public function snapshot(BuildRequest $request, int $exitCode, string $log): string
    {
        $id = date('Ymd-His') . '-' . bin2hex(random_bytes(3));
        $dir = $this->dir($id);
        if (!is_dir($dir) && !mkdir($dir, 0770, true) && !is_dir($dir)) {
            throw new \RuntimeException(sprintf('Cannot create %s.', $dir));
        }

        $this->write($dir . '/build.log', LogRedactor::redact($log));
        $this->write($dir . '/build.json', self::encode([
            'id' => $id,
            'exit_code' => $exitCode,
            'scope' => $request->isFull() ? 'full' : 'repositories',
            'repositories' => array_map(LogRedactor::redact(...), $request->getRepositoryUrls()),
            'finished_at' => date(\DATE_ATOM),
        ]));
        $this->prune();

        return $id;
    }

    public function exists(string $id): bool
    {
        return 1 === preg_match(self::ID, $id) && is_file($this->dir($id) . '/build.json');
    }

    /**
     * @return array<string, mixed>
     */
    public function build(string $id): array
    {
        if (!$this->exists($id)) {
            throw new \InvalidArgumentException(sprintf('No failed build is kept as "%s".', $id));
        }

        return (array) json_decode((string) file_get_contents($this->dir($id) . '/build.json'), true);
    }

    public function logPath(string $id): string
    {
        return $this->dir($id) . '/build.log';
    }

    /**
     * Records the outcome of a diagnosis, with its build, as the newest.
     *
     * @param array<string, mixed> $outcome
     */
    public function save(string $id, array $outcome): void
    {
        $json = self::encode(['build' => $this->build($id)] + $outcome);
        $this->write($this->dir($id) . '/diagnosis.json', $json);
        $this->write($this->config->getDir() . '/last.json', $json);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function last(): ?array
    {
        $file = $this->config->getDir() . '/last.json';

        return is_file($file) ? (array) json_decode((string) file_get_contents($file), true) : null;
    }

    public function dir(string $id): string
    {
        if (1 !== preg_match(self::ID, $id)) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a diagnosis id.', $id));
        }

        return $this->config->getDir() . '/' . $id;
    }

    /**
     * Written to a temporary name and renamed, so a reader never sees half.
     */
    private function write(string $file, string $contents): void
    {
        $temporary = $file . '.' . bin2hex(random_bytes(4));
        if (false === file_put_contents($temporary, $contents) || !rename($temporary, $file)) {
            @unlink($temporary);
            throw new \RuntimeException(sprintf('Cannot write %s.', $file));
        }
    }

    private function prune(): void
    {
        $dirs = glob($this->config->getDir() . '/*', \GLOB_ONLYDIR) ?: [];
        sort($dirs);
        foreach (array_slice($dirs, 0, max(0, count($dirs) - self::KEEP)) as $dir) {
            array_map('unlink', glob($dir . '/*') ?: []);
            @rmdir($dir);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function encode(array $data): string
    {
        return json_encode($data, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR) . "\n";
    }
}
