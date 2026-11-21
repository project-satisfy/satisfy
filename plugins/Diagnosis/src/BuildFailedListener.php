<?php

namespace Playbloom\Plugin\Diagnosis;

use Playbloom\Event\BuildFinishedEvent;
use Playbloom\Runner\SatisBuildRunner;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * When a build fails, keeps a redacted copy of its log, which the next build
 * would replace, and starts its diagnosis.
 */
#[AsEventListener]
final class BuildFailedListener
{
    private const LOG_BYTES = 262144;

    public function __construct(
        private readonly DiagnosisConfig $config,
        private readonly DiagnosisStore $store,
        private readonly SatisBuildRunner $runner,
        private readonly Launcher $launcher,
    ) {
    }

    public function __invoke(BuildFinishedEvent $event): void
    {
        if ($event->isSuccessful() || !$this->config->isConfigured()) {
            return;
        }

        $id = $this->store->snapshot($event->getRequest(), $event->getExitCode(), $this->runner->getLastLog(self::LOG_BYTES));
        $this->launcher->launch($id);
    }
}
