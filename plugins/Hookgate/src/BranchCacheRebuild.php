<?php

namespace Playbloom\Plugin\Hookgate;

use Playbloom\Event\BuildEvent;
use Playbloom\Event\WebhookReceivedEvent;
use Playbloom\Queue\BuildQueue;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Builds a GitLab repository once more, HOOKGATE_REFORWARD_DELAY seconds after
 * its last branch push.
 *
 * GitLab answers GET /projects/:id/repository/branches from a cache kept for
 * 30 seconds per user, and Composer's GitLab driver reads every branch head
 * from it with the one token every build uses. A build for a push that lands
 * within 30 seconds of another build of the project can list the old head,
 * exit 0 and publish nothing. Any cache entry still alive 35 seconds after the
 * push was filled after it, so one more build then lists the branch as it is.
 *
 * Every branch push restarts the wait, so a run of pushes makes one extra
 * build. Tags are not cached by GitLab, so a tag push queues nothing extra.
 */
final class BranchCacheRebuild implements EventSubscriberInterface
{
    private bool $branchPush = false;

    public function __construct(
        private readonly HookgateConfig $config,
        private readonly BuildQueue $queue,
        private readonly HookgateLog $log,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            WebhookReceivedEvent::class => 'onWebhookReceived',
            // After BuildQueue::onBuild, which queues the build itself.
            BuildEvent::class => ['onBuild', -10],
        ];
    }

    public function onWebhookReceived(WebhookReceivedEvent $event): void
    {
        $this->branchPush = 'gitlab' === $event->getProvider()
            && $this->config->getRebuildDelay() > 0
            && null !== $event->getPushedBranch();
    }

    public function onBuild(BuildEvent $event): void
    {
        $url = $event->getRepository()?->getUrl();
        if (!$this->branchPush || !$event->isQueued() || null === $url) {
            return;
        }
        $this->branchPush = false;

        $delay = $this->config->getRebuildDelay();
        try {
            $this->queue->enqueue($url, $delay);
            $this->log->write('rebuild_scheduled', ['repository' => $url, 'delay' => $delay]);
        } catch (\Throwable $exception) {
            // The build for the push itself is queued; only the second one is lost.
            $this->log->write('rebuild_failed', ['repository' => $url, 'error' => $exception->getMessage()]);
        }
    }
}
