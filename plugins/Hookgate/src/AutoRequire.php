<?php

namespace Playbloom\Plugin\Hookgate;

use Playbloom\Event\WebhookReceivedEvent;
use Playbloom\Exception\ConfigurationChangedException;
use Playbloom\Persister\JsonPersister;
use Playbloom\Queue\BuildQueue;
use Playbloom\Service\Manager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Publishes a package because a tag was pushed to it.
 *
 * satis.json needs both a repositories entry and a require entry for a package
 * to be published, and only a person used to add the second. On a GitLab tag
 * push this adds both, if the tagged composer.json is a package the catalogue
 * publishes and its name is not required yet; then the build publishes it.
 *
 * HOOKGATE_AUTO_REQUIRE: off; observe (the default) decides and logs
 * auto_require_would_add; on decides, checks and writes.
 *
 * In on mode the delivery is answered at once and the work is finished after
 * the response, on kernel.terminate, because the checker can take a minute.
 * The check runs without the lock on satis.json; the result is written only if
 * the file has not changed meanwhile, and planned again if it has.
 */
final class AutoRequire implements EventSubscriberInterface
{
    private const ATTEMPTS = 3;

    /** Refusals that are expected, logged as skipped rather than refused. */
    private const EXPECTED = ['type_project', 'drupal7', 'served_by_drupal_org', 'blacklisted'];

    /** @var array<int, array{url: string, package: string, log: array<string, string>}> */
    private array $pending = [];

    public function __construct(
        private readonly HookgateConfig $config,
        private readonly Http $http,
        private readonly PackageRules $rules,
        private readonly Checker $checker,
        private readonly Manager $manager,
        private readonly JsonPersister $persister,
        private readonly BuildQueue $queue,
        private readonly HookgateLog $log,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            WebhookReceivedEvent::class => 'onWebhookReceived',
            KernelEvents::TERMINATE => 'onTerminate',
        ];
    }

    public function onWebhookReceived(WebhookReceivedEvent $event): void
    {
        $mode = $this->config->getAutoRequire();
        $tag = $event->getCreatedTag();
        $project = $event->getPayload()['project'] ?? [];
        $url = is_array($project) ? (string) ($project['git_http_url'] ?? '') : '';
        $path = is_array($project) ? (string) ($project['path_with_namespace'] ?? '') : '';
        if ('gitlab' !== $event->getProvider() || 'off' === $mode || null === $tag || '' === $url || '' === $path) {
            return;
        }

        $log = ['project' => $path, 'tag' => $tag, 'mode' => $mode];
        // The project's URL picks the host the API token is sent to, and the
        // repository written to satis.json: only GitLab may name it.
        if (!$event->isAuthenticated()) {
            $this->log->write('auto_require_refused', $log + ['reason' => 'unauthenticated']);

            return;
        }
        if (!$this->http->hasApiToken()) {
            $this->log->write('auto_require_refused', $log + ['reason' => 'no_api_token']);

            return;
        }

        try {
            $decision = $this->decide($url, $path, $tag);
        } catch (AutoRequireRefused $refusal) {
            $this->logRefusal($log, $refusal);

            return;
        } catch (\Throwable $exception) {
            $this->log->write('auto_require_error', $log + ['error' => $exception->getMessage()]);

            return;
        }
        if (null === $decision) {
            return;
        }

        if ('observe' === $mode) {
            $this->log->write('auto_require_would_add', $log + $decision);

            return;
        }

        $this->pending[] = ['url' => $url, 'package' => $decision['package'], 'log' => $log];
        $event->accept(sprintf("accepted: adding %s to require, then building\n", $decision['package']));
    }

    /**
     * Writes what was accepted, then queues the build that publishes it.
     */
    public function onTerminate(): void
    {
        $pending = $this->pending;
        $this->pending = [];
        foreach ($pending as $item) {
            try {
                $changes = $this->apply($item['url'], $item['package']);
                if ([] !== $changes) {
                    $this->log->write('auto_require_added', $item['log'] + ['package' => $item['package'], 'changes' => $changes]);
                }
            } catch (AutoRequireRefused $refusal) {
                $this->logRefusal($item['log'], $refusal);
            } catch (\Throwable $exception) {
                $this->log->write('auto_require_error', $item['log'] + ['error' => $exception->getMessage()]);
            }
            // The delivery was answered without a build; queue one either way,
            // as the webhook would have, if the repository is in the catalogue.
            $this->manager->reload();
            if (null !== $this->manager->findByUrl('#^' . preg_quote($item['url'], '#') . '$#')) {
                $this->queue->enqueue($item['url']);
            }
        }
    }

    /**
     * What publishing this tag would change, or null when there is nothing to do.
     *
     * @throws AutoRequireRefused
     *
     * @return array{package: string, changes: string[]}|null
     */
    public function decide(string $url, string $path, string $tag): ?array
    {
        $project = rawurlencode($path);
        [$status, $body] = $this->http->api($url, '/projects/' . $project . '/repository/files/composer%2Ejson/raw?ref=' . rawurlencode($tag));
        if (404 === $status) {
            return null;
        }
        if (200 !== $status) {
            throw new AutoRequireRefused('gitlab_unavailable', sprintf('GitLab answered %d for composer.json at %s', $status, $tag));
        }
        $composer = json_decode($body, true);
        if (!is_array($composer)) {
            throw new AutoRequireRefused('composer_json_invalid', sprintf('composer.json at %s is not valid JSON', $tag));
        }

        // A tag on a package already published, the common case, needs nothing.
        $name = strtolower(trim((string) ($composer['name'] ?? '')));
        if ('' !== $name && [] === $this->changesFor($url, $name)) {
            return null;
        }

        [$status, $body] = $this->http->api($url, '/projects/' . $project . '/repository/tree?per_page=100&ref=' . rawurlencode($tag));
        if (200 !== $status) {
            throw new AutoRequireRefused('gitlab_unavailable', sprintf('GitLab answered %d for the files at %s', $status, $tag));
        }
        $rootFiles = [];
        foreach ((array) json_decode($body, true) as $item) {
            if (is_array($item) && 'blob' === ($item['type'] ?? '') && isset($item['name'])) {
                $rootFiles[] = (string) $item['name'];
            }
        }
        $name = $this->rules->validate($tag, $composer, $rootFiles);

        if (CatalogueEdit::contains($this->manager->reload()->getBlacklist(), $name)) {
            throw new AutoRequireRefused('blacklisted', sprintf("%s is in satis.json's blacklist: a person decided not to publish it", $name));
        }
        if (str_starts_with($name, 'drupal/')) {
            $served = $this->http->drupalOrgServes($name);
            if (null === $served) {
                throw new AutoRequireRefused('drupal_org_unavailable', sprintf('could not ask drupal.org whether it serves %s', $name));
            }
            if ($served) {
                throw new AutoRequireRefused('served_by_drupal_org', sprintf('drupal.org serves %s; a copy here would shadow it for every site', $name));
            }
        }

        $changes = $this->changesFor($url, $name);

        return [] === $changes ? null : ['package' => $name, 'changes' => $changes];
    }

    /**
     * Plans, checks and writes the edit; returns what changed.
     *
     * @throws AutoRequireRefused
     *
     * @return string[]
     */
    public function apply(string $url, string $package): array
    {
        for ($attempt = 1; $attempt <= self::ATTEMPTS; ++$attempt) {
            $version = $this->manager->getVersion();
            $config = $this->manager->reload();
            if ($config->isRequireAll()) {
                throw new AutoRequireRefused('require_all', 'satis.json has require-all, so it has no require list to add to');
            }
            $changes = CatalogueEdit::plan($config, $url, $package);
            if ([] === $changes) {
                return [];
            }

            [$exit, $report] = $this->checker->check($this->persister->serialize($config), $url);
            if (0 !== $exit && 4 !== $exit) {
                throw new AutoRequireRefused(match ($exit) {
                    1 => 'check_blocking', 2 => 'check_incomplete', default => 'check_failed'
                }, match ($exit) {
                    1 => 'the checker finds blocking problems in satis.json with this package added', 2 => 'the checker could not complete', default => sprintf('the checker could not run (exit %d)', $exit),
                }, $report);
            }

            try {
                $this->manager->flush($version);

                return $changes;
            } catch (ConfigurationChangedException) {
                $this->log->write('auto_require_replanned', ['attempt' => $attempt, 'package' => $package]);
            }
        }

        throw new AutoRequireRefused('concurrent_edits', sprintf('satis.json kept changing during the check (%d attempts)', self::ATTEMPTS));
    }

    /**
     * What the edit would change, planned on a copy that is then thrown away.
     *
     * @return string[]
     */
    private function changesFor(string $url, string $package): array
    {
        $changes = CatalogueEdit::plan($this->manager->reload(), $url, $package);
        $this->manager->reload();

        return $changes;
    }

    /**
     * @param array<string, string> $log
     */
    private function logRefusal(array $log, AutoRequireRefused $refusal): void
    {
        $this->log->write(in_array($refusal->reason, self::EXPECTED, true) ? 'auto_require_skipped' : 'auto_require_refused', $log + [
            'reason' => $refusal->reason,
            'message' => $refusal->getMessage(),
            'detail' => '' === $refusal->detail ? null : substr($refusal->detail, -1000),
        ]);
    }
}
