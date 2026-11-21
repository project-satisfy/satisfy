<?php

namespace Playbloom\Plugin\Hookgate;

use Playbloom\Event\RepositoryAddingEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Lets the GitLab webhook auto-add a repository only if some branch or tag
 * has a composer.json declaring a type the catalogue publishes.
 *
 * A repository without one is a build waiting to fail: Composer throws on it
 * the first time a build widens its pool, and the whole build dies.
 *
 * Fails open: when GitLab cannot be asked, or does not answer clearly, the
 * repository is added, as Satisfy would without this plugin. Only a clear
 * "no composer.json" or a type outside the list refuses.
 */
final class RegistrationGate implements EventSubscriberInterface
{
    public function __construct(
        private readonly Http $http,
        private readonly PackageTypes $types,
        private readonly HookgateLog $log,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [RepositoryAddingEvent::class => 'onRepositoryAdding'];
    }

    public function onRepositoryAdding(RepositoryAddingEvent $event): void
    {
        if ('gitlab' !== $event->getProvider()) {
            return;
        }
        $url = $event->getRepository()->getUrl();
        // The URL picks the host the API token is sent to: only GitLab may name it.
        if (!$event->isAuthenticated()) {
            $this->log->write('skipped_unauthenticated', ['url' => $url]);
            $event->refuse('the delivery was not checked against a secret');

            return;
        }
        if (!$this->http->hasApiToken()) {
            $this->log->write('registered_no_api_token', ['url' => $url]);

            return;
        }
        $path = self::projectPath($event->getPayload(), $url);
        if (null === $path) {
            $this->log->write('registered_unparseable_url', ['url' => $url]);

            return;
        }

        [$verdict, $type] = $this->verdict($url, $path);
        switch ($verdict) {
            case 'package':
                $this->log->write('registered_new_package', ['project' => $path, 'type' => $type]);

                return;
            case 'excluded':
                // Two decisions: a site declaring "project" is expected several
                // times a day; any other unlisted type is a package nobody can
                // publish, and worth a person's look.
                $this->log->write('project' === $type ? 'skipped_type_project' : 'skipped_type_unlisted', ['project' => $path, 'type' => $type]);
                $event->refuse(sprintf('composer.json declares type "%s", which this catalogue does not publish', $type));

                return;
            case 'absent':
                $this->log->write('skipped_not_a_package', ['project' => $path]);
                $event->refuse('no composer.json on any branch or tag');

                return;
            default:
                $this->log->write('registered_undetermined', ['project' => $path]);
        }
    }

    /**
     * The project's path on GitLab, from the payload or from its URL.
     *
     * @param array<string, mixed> $payload
     */
    public static function projectPath(array $payload, string $url): ?string
    {
        $project = is_array($payload['project'] ?? null) ? $payload['project'] : [];
        if (!empty($project['path_with_namespace'])) {
            return (string) $project['path_with_namespace'];
        }
        if (preg_match('#^https?://[^/]+/(.+?)(?:\.git)?$#', $url, $matches)
            || preg_match('#^[^@]+@[^:]+:(.+?)(?:\.git)?$#', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Whether the project is a package of a type the catalogue publishes.
     * Existence is looked for on every branch and tag, since a package whose
     * composer.json is only on a tag is valid; the type is read from the
     * first ref that has one, which is the default branch in practice.
     *
     * @return array{0: string, 1: ?string} package, excluded, absent or unknown; and the type
     */
    private function verdict(string $url, string $path): array
    {
        $project = rawurlencode($path);
        [$status, $body] = $this->http->api($url, '/projects/' . $project);
        if (404 === $status) {
            return ['absent', null];
        }
        if (200 !== $status) {
            return ['unknown', null];
        }

        $refs = [];
        $data = json_decode($body, true);
        if (!empty($data['default_branch'])) {
            $refs[] = (string) $data['default_branch'];
        }
        foreach (['branches', 'tags'] as $kind) {
            [$status, $body] = $this->http->api($url, '/projects/' . $project . '/repository/' . $kind . '?per_page=100');
            if (200 !== $status) {
                continue;
            }
            foreach ((array) json_decode($body, true) as $item) {
                if (!empty($item['name'])) {
                    $refs[] = (string) $item['name'];
                }
            }
        }
        $refs = array_unique($refs);
        if ([] === $refs) {
            return ['absent', null];
        }

        $indeterminate = false;
        foreach ($refs as $ref) {
            [$status, $body] = $this->http->api($url, '/projects/' . $project . '/repository/files/composer%2Ejson/raw?ref=' . rawurlencode($ref));
            if (200 === $status) {
                $composer = json_decode($body, true);
                if (!is_array($composer)) {
                    // Not ours to judge: Satis will say so loudly at the next build.
                    return ['unknown', null];
                }
                $type = PackageTypes::of($composer);

                return [$this->types->allows($type) ? 'package' : 'excluded', $type];
            }
            if (404 !== $status) {
                $indeterminate = true;
            }
        }

        return [$indeterminate ? 'unknown' : 'absent', null];
    }
}
