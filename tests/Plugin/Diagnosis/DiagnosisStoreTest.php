<?php

namespace Playbloom\Tests\Plugin\Diagnosis;

use Playbloom\Model\BuildRequest;

class DiagnosisStoreTest extends DiagnosisTestCase
{
    public function testAFailedBuildIsKeptRedacted(): void
    {
        $id = $this->keepFailedBuild();

        $this->assertTrue($this->store->exists($id));
        $log = (string) file_get_contents($this->store->logPath($id));
        $this->assertStringNotContainsString('s3cret', $log);
        $this->assertStringContainsString('https://***@git.acme.test/acme/app.git', $log);

        $build = $this->store->build($id);
        $this->assertSame(1, $build['exit_code']);
        $this->assertSame('repositories', $build['scope']);
        $this->assertSame(['https://***@git.acme.test/acme/app.git'], $build['repositories']);
    }

    public function testTheNewestOutcomeIsTheLast(): void
    {
        $this->assertNull($this->store->last());

        $first = $this->keepFailedBuild();
        $this->store->save($first, ['status' => 'failed']);
        $second = $this->store->snapshot(BuildRequest::full(), 2, 'Exit code 2');
        $this->store->save($second, ['status' => 'done', 'diagnosis' => 'Cause: x']);

        $last = $this->store->last();
        $this->assertSame('done', $last['status']);
        $this->assertSame($second, $last['build']['id']);
        $this->assertSame('full', $last['build']['scope']);
    }

    public function testAnIdThatIsNotOneIsRefused(): void
    {
        $this->assertFalse($this->store->exists('../../etc'));
        $this->expectException(\InvalidArgumentException::class);
        $this->store->dir('../../etc');
    }
}
