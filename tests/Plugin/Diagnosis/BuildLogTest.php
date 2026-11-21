<?php

namespace Playbloom\Tests\Plugin\Diagnosis;

use Playbloom\Plugin\Diagnosis\Tool\BuildLog;

class BuildLogTest extends DiagnosisTestCase
{
    public function testSearchGivesMatchingLinesWithTheirNumbers(): void
    {
        $log = new BuildLog($this->store->logPath($this->keepFailedBuild()));

        $this->assertSame("4: fatal: unable to access 'https://git.acme.test/acme/app.git/': Could not resolve host: git.acme.test", $log->search('could not resolve'));
        $this->assertSame('No line matches.', $log->search('nothing like this'));
        $this->assertSame('That is not a valid regular expression.', $log->search('('));
    }

    public function testLinesAreReadByNumber(): void
    {
        $log = new BuildLog($this->store->logPath($this->keepFailedBuild()));

        $this->assertSame("5: In ArchiveManager.php line 160:\n6:   The archive directory is not writable.", $log->lines(5, 2));
        $this->assertSame('The log has 8 lines.', $log->lines(100));
    }
}
