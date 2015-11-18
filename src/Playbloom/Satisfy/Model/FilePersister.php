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
    /** @var Filesystem */
    private $filesystem;

    /** @var string */
    private $filename;

    /** @var string */
    private $auditlog;

    /**
     * @param Filesystem $filesystem
     * @param string $filename
     * @param string $auditlog
     */
    public function __construct(Filesystem $filesystem, $filename, $auditlog)
    {
        $this->filesystem = $filesystem;

        $filename = realpath($filename);

        if (!$this->filesystem->exists($filename)) {
            throw new InvalidArgumentException(sprintf('The file "%s" is unavailable', $filename));
        }

        if (!$this->filesystem->exists($auditlog)) {
            throw new InvalidArgumentException(sprintf('The audit log directory "%s" is unavailable', $filename));
        }

        $this->filename = $filename;
        $this->auditlog = $auditlog;
    }

    /**
     * Load content from file
     *
     * @return string
     */
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

    /**
     * Flush content to file
     *
     * @param string $content
     */
    public function flush($content)
    {
        try {
            $this->checkPermissions();

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

    /**
     * Generate filename for file backup
     *
     * @return string
     */
    public function generateBackupFilename()
    {
        $path = rtrim($this->auditlog, '/');
        $name = sprintf('%s.json', (new DateTime())->format('Y-m-d_his'));

        return $path.'/'.$name;
    }

    /**
     * Checks write permission on all needed paths.
     *
     * @throws \Symfony\Component\Filesystem\Exception\IOException
     */
    protected function checkPermissions()
    {
        $paths = array(
            $this->auditlog,
            $this->filename
        );

        foreach($paths as $path) {
            if(!is_writeable($path)) {
                throw new IOException(sprintf('Path "%s" is not writeable.', $path));
            }
        }
    }

    /**
     * @param string $filename
     * @param string $content
     */
    protected function dumpFile($filename, $content)
    {
        $handle = fopen($filename, 'r+');
        if (!$handle) {
            throw new IOException(sprintf('Failed to open file "%s" for write', $filename), 0, null, $filename);
        }

        $locked = flock($handle, LOCK_EX | LOCK_NB);
        if (!$locked) {
            throw new IOException(sprintf('Failed to lock file "%s"', $filename), 0, null, $filename);
        }

        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, $content);
        fclose($handle);
    }
}
