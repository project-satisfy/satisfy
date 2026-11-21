<?php

namespace Playbloom\Persister;

use Playbloom\Exception\MissingConfigException;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Reads and writes satis.json.
 *
 * Writes happen in place, under an exclusive flock() on the file itself, so
 * that a single-file bind mount keeps pointing at the file and anything else
 * that writes it under the same lock does not lose its changes or ours.
 */
class FilePersister implements LockablePersisterInterface
{
    /** @var Filesystem */
    private $filesystem;

    /** @var string */
    private $filename;

    /** @var string */
    private $logPath;

    /** @var resource|null the locked file, while the lock is held */
    private $handle;

    private int $lockDepth = 0;

    public function __construct(Filesystem $filesystem, string $filename, string $logPath, private readonly int $lockTimeout = 10)
    {
        $this->filesystem = $filesystem;
        $this->filename = $filename;
        $this->logPath = $logPath;
    }

    /**
     * Load content from file
     *
     * @throws MissingConfigException When config file is missing or empty
     *
     * @return string
     */
    public function load()
    {
        if (!$this->filesystem->exists($this->filename)) {
            throw new MissingConfigException('Satis file is missing');
        }

        try {
            $content = trim($this->read());
        } catch (\Exception $exception) {
            throw new \RuntimeException(sprintf('Unable to load the data from "%s"', $this->filename), 0, $exception);
        }

        if (empty($content)) {
            throw new MissingConfigException('Satis file is empty');
        }

        return $content;
    }

    /**
     * Flush content to file, in place and under the lock.
     *
     * @param string $content
     *
     * @throws \RuntimeException
     */
    public function flush($content): void
    {
        try {
            $this->checkPermissions();
            $this->lock();
            try {
                $this->createBackup();
                $this->write((string) $content);
            } finally {
                $this->unlock();
            }
        } catch (\Exception $exception) {
            throw new \RuntimeException(sprintf('Unable to persist the data to "%s"', $this->filename), 0, $exception);
        }
    }

    public function lock(): void
    {
        if ($this->lockDepth++ > 0) {
            return;
        }

        $handle = @fopen($this->filename, 'c+');
        if (false === $handle) {
            $this->lockDepth = 0;
            throw new IOException(sprintf('Failed to open file "%s".', $this->filename), 0, null, $this->filename);
        }

        $deadline = microtime(true) + $this->lockTimeout;
        while (!flock($handle, LOCK_EX | LOCK_NB)) {
            if (microtime(true) >= $deadline) {
                fclose($handle);
                $this->lockDepth = 0;
                throw new IOException(sprintf('File "%s" is locked by another writer.', $this->filename), 0, null, $this->filename);
            }
            usleep(100000);
        }
        $this->handle = $handle;
    }

    public function unlock(): void
    {
        if (0 === $this->lockDepth || --$this->lockDepth > 0) {
            return;
        }
        if (null !== $this->handle) {
            flock($this->handle, LOCK_UN);
            fclose($this->handle);
            $this->handle = null;
        }
    }

    public function getVersion(): string
    {
        if (!$this->filesystem->exists($this->filename)) {
            return sha1('');
        }

        return sha1($this->read());
    }

    /**
     * Create backup file for current configuration.
     */
    public function createBackup()
    {
        if (!file_exists($this->filename)) {
            return;
        }
        if (!$this->filesystem->exists($this->logPath) || !is_writable($this->logPath)) {
            return;
        }

        $path = rtrim($this->logPath, '/');
        $name = sprintf('%s.json', date('Y-m-d_His'));
        $this->filesystem->copy($this->filename, $path . '/' . $name);
    }

    /**
     * Checks write permission on all needed paths.
     *
     * @throws IOException
     */
    protected function checkPermissions()
    {
        if (file_exists($this->filename)) {
            if (!is_writable($this->filename)) {
                throw new IOException(sprintf('File "%s" is not writable.', $this->filename));
            }
        } else {
            if (!is_writable(dirname($this->filename))) {
                throw new IOException(sprintf('Path "%s" is not writable.', dirname($this->filename)));
            }
        }
    }

    /**
     * The file as it is now: through the locked handle while the lock is held,
     * since another descriptor would see the same bytes but not hold the lock.
     */
    private function read(): string
    {
        if (null !== $this->handle) {
            rewind($this->handle);

            return (string) stream_get_contents($this->handle);
        }

        return (string) file_get_contents($this->filename);
    }

    private function write(string $content): void
    {
        $handle = $this->handle;
        if (null === $handle
            || !ftruncate($handle, 0)
            || !rewind($handle)
            || strlen($content) !== fwrite($handle, $content)
            || !fflush($handle)) {
            throw new IOException(sprintf('Failed to write file "%s".', $this->filename), 0, null, $this->filename);
        }
    }
}
