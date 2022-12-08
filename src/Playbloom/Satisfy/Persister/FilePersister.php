<?php

namespace Playbloom\Satisfy\Persister;

use Exception;
use Playbloom\Satisfy\Exception\MissingConfigException;
use RuntimeException;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class FilePersister implements PersisterInterface
{
    /** @var Filesystem */
    private $filesystem;

    /** @var string */
    private $filename;

    /** @var string */
    private $logPath;

    public function __construct(Filesystem $filesystem, string $filename, string $logPath)
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
            $content = trim(file_get_contents($this->filename));
        } catch (\Exception $exception) {
            throw new \RuntimeException(sprintf('Unable to load the data from "%s"', $this->filename), 0, $exception);
        }

        if (empty($content)) {
            throw new MissingConfigException('Satis file is empty');
        }

        return $content;
    }

    /**
     * Flush content to file
     *
     * @param string $content
     *
     * @throws \RuntimeException
     */
    public function flush($content): void
    {
        try {
            $this->checkPermissions();
            $this->createBackup();
            if (false === @file_put_contents($this->filename, $content)) {
                throw new IOException(sprintf('Failed to write file "%s".', $this->filename), 0, null, $this->filename);
            }
        } catch (\Exception $exception) {
            throw new \RuntimeException(sprintf('Unable to persist the data to "%s"', $this->filename), 0, $exception);
        }
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
        $name = sprintf('%s.json', date('Y-m-d_his'));
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
}
