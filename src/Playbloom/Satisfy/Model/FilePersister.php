<?php

namespace Playbloom\Satisfy\Model;

use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Exception;
use RuntimeException;
use InvalidArgumentException;
use DateTime;

class FilePersister implements PersisterInterface
{
    private $filesystem;
    private $filename;
    private $auditlog;

    public function __construct(Filesystem $filesystem, $filename, $auditlog)
    {
        $this->filesystem = $filesystem;

        if (!$this->filesystem->exists($filename)) {
            throw new InvalidArgumentException(sprintf('The file "%s" is unavailable', $filename));
        }

        if (!$this->filesystem->exists($auditlog)) {
            throw new InvalidArgumentException(sprintf('The audit log directory "%s" is unavailable', $filename));
        }

        $this->filename = $filename;
        $this->auditlog = $auditlog;
    }

    public function load()
    {
        try {
            $content = file_get_contents($this->filename);
        } catch (Exception $exception) {
            throw new RuntimeException(
                sprintf('Unable to load the data from "%s"', $this->filename),
                null,
                $exception
            );
        }

        return $content;
    }

    public function flush($content)
    {
        try {
            $backupFilename = $this->generateBackupFilename();

            $this->filesystem->copy($this->filename, $backupFilename);
            $this->dumpFile($this->filename, $content);
        } catch (Exception $exception) {
            throw new RuntimeException(
                sprintf('Unable to persist the data to "%s"', $this->filename),
                null,
                $exception
            );
        }
    }

    public function generateBackupFilename()
    {
        $path = rtrim($this->auditlog, '/');
        $name = sprintf('%s.json', (new DateTime())->format('Y-m-d_his'));

        return $path.'/'.$name;
    }

    protected function dumpFile($filename, $content)
    {
        if (false === @file_put_contents($filename, $content)) {
            throw new IOException(sprintf('Failed to write file "%s".', $filename));
        }
    }
}
