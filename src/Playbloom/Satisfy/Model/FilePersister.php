<?php

namespace Playbloom\Satisfy\Model;

use League\Flysystem\FilesystemInterface;
use RuntimeException;
use InvalidArgumentException;
use DateTime;

class FilePersister implements PersisterInterface
{
    /** @var FilesystemInterface */
    private $filesystem;

    /** @var string */
    private $filename;

    /** @var string */
    private $auditlog;

    /**
     * @param FilesystemInterface $filesystem
     * @param string $filename
     * @param string $auditlog
     */
    public function __construct(FilesystemInterface $filesystem, $filename, $auditlog)
    {
        $this->filesystem = $filesystem;

        if (!$this->filesystem->has($filename)) {
            throw new InvalidArgumentException(sprintf('The file "%s" is unavailable', $filename));
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
        $content = $this->filesystem->read($this->filename);

        if ($content === false) {
            throw new RuntimeException(
                sprintf('Unable to load the data from "%s"', $this->filename),
                null
            );
        }

        return $content;
    }

    /**
     * Flush content to file
     *
     * @param string $content
     * @throws RuntimeException
     */
    public function flush($content)
    {
        $backupFilename = $this->generateBackupFilename();

        $writeBackup = $this->filesystem->copy($this->filename, $backupFilename);
        if ($writeBackup === false) {
            throw new RuntimeException(
                sprintf('Unable to persist the backup data to "%s"', $backupFilename),
                null
            );
        }

        $write = $this->filesystem->put($this->filename, $content);
        if ($write === false) {
            throw new RuntimeException(
                sprintf('Unable to persist the data to "%s"', $this->filename),
                null
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
}
