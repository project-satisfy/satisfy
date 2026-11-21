<?php

namespace Playbloom\Tests\Plugin\Diagnosis;

use PHPUnit\Framework\TestCase;
use Playbloom\Model\BuildRequest;
use Playbloom\Plugin\Diagnosis\DiagnosisConfig;
use Playbloom\Plugin\Diagnosis\DiagnosisStore;
use Symfony\Component\Filesystem\Filesystem;

/**
 * A diagnosis directory of its own, and a failed build's log with credentials in it.
 */
abstract class DiagnosisTestCase extends TestCase
{
    protected const LOG = <<<'LOG'
        $ 'bin/satis' 'build' 'satis.json' 'public' '--skip-errors' '--no-ansi' '--verbose'
        Scanning packages
        Skipping Exception 'Failed to execute git clone --mirror -- https://deploy:s3cret@git.acme.test/acme/app.git /cache/vcs/app.git/
        fatal: unable to access 'https://git.acme.test/acme/app.git/': Could not resolve host: git.acme.test
        In ArchiveManager.php line 160:
          The archive directory is not writable.

        Exit code 1

        LOG;

    protected string $dir;
    protected DiagnosisConfig $config;
    protected DiagnosisStore $store;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/satisfy-diagnosis-' . bin2hex(random_bytes(4));
        $this->config = new DiagnosisConfig($this->dir, 'https://models.test', 'test-model');
        $this->store = new DiagnosisStore($this->config);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->dir);
    }

    protected function keepFailedBuild(): string
    {
        return $this->store->snapshot(BuildRequest::forRepositories(['https://deploy:s3cret@git.acme.test/acme/app.git']), 1, self::LOG);
    }
}
