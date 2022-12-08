<?php

namespace Playbloom\Satisfy\Validator;

use Playbloom\Satisfy\Service\Manager;
use Symfony\Component\Filesystem\Filesystem;

class EnvValidator
{
    private string $basePath;

    private string $satisFilename;

    private string $composerHome;

    private string $outputDir;

    public function __construct(string $root, string $satisFilename, string $composerHome, Manager $manager)
    {
        $this->basePath = $root;
        $this->satisFilename = $satisFilename;
        $this->composerHome = $composerHome;
        $this->outputDir = $manager->getConfig()->getOutputDir();
    }

    public function validate(): void
    {
        $filesystem = new Filesystem();
        if (!is_file($this->satisFilename)) {
            throw new \RuntimeException('Missing satis configuration file');
        }
        if (!is_writable($this->satisFilename)) {
            throw new \RuntimeException('Satis configuration file is read-only');
        }

        $outputDir = $this->outputDir;
        if (!$filesystem->isAbsolutePath($outputDir)) {
            $outputDir = $this->basePath . DIRECTORY_SEPARATOR . $outputDir;
        }
        if (!is_writable($outputDir)) {
            throw new \RuntimeException('Output (WEB) directory is read-only or missing');
        }
        if (!is_dir($this->composerHome)) {
            throw new \RuntimeException('Missing composer home directory');
        }
        if (!is_writable($this->composerHome)) {
            throw new \RuntimeException('Composer home directory is read-only');
        }
    }
}
