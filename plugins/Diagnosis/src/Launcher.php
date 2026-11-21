<?php

namespace Playbloom\Plugin\Diagnosis;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Process\Process;

/**
 * Starts `satisfy:diagnose <id>` in a process of its own and returns at once.
 * The worker holds the build lock while BuildFinishedEvent is dispatched, and
 * a model can take a minute to answer.
 *
 * The process starts without COMPOSER_AUTH, which the worker holds to clone
 * private repositories and which the diagnosis has no use for. Its output goes
 * to diagnose.log in the build's directory.
 */
class Launcher
{
    public function __construct(
        private readonly DiagnosisStore $store,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
    }

    public function launch(string $id): void
    {
        $process = new Process(
            ['sh', '-c', 'exec "$@" >>"$0" 2>&1', $this->store->dir($id) . '/diagnose.log', \PHP_BINARY, $this->projectDir . '/bin/console', 'satisfy:diagnose', $id],
            $this->projectDir,
            ['COMPOSER_AUTH' => false],
        );
        // Left running when this object goes, instead of being stopped.
        $process->setOptions(['create_new_console' => true]);
        $process->disableOutput();
        $process->start();
    }
}
