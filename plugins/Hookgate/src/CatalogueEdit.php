<?php

namespace Playbloom\Plugin\Hookgate;

use Playbloom\Model\Configuration;
use Playbloom\Model\PackageConstraint;
use Playbloom\Model\Repository;

/**
 * The edit auto-require makes: one repositories entry per project, spelled as
 * GitLab spells it, and a require entry for the package.
 *
 * The webhook finds a repository by its exact URL, so any other spelling of
 * the same project makes it add a second entry on the next push; existing
 * duplicates are collapsed onto GitLab's spelling.
 */
final class CatalogueEdit
{
    /**
     * Applies the edit to $config and says what changed; nothing, when the
     * package is already published from that repository.
     *
     * @return string[]
     */
    public static function plan(Configuration $config, string $url, string $package): array
    {
        $key = self::projectKey($url);
        if (null === $key) {
            throw new \InvalidArgumentException(sprintf('Cannot read a project out of "%s".', $url));
        }

        $changes = [];
        $repositories = $config->getRepositories();
        $matches = [];
        foreach ($repositories as $id => $repository) {
            if (self::projectKey($repository->getUrl()) === $key) {
                $matches[$id] = $repository;
            }
        }

        if ([] === $matches) {
            $repository = new Repository($url, 'vcs');
            $repositories->offsetSet($repository->getId(), $repository);
            $changes[] = 'repositories: added ' . $url;
        } else {
            // Keep the entry already spelled as GitLab spells it, else the
            // first; its type is kept, since changing a working entry is not
            // this edit's business.
            $keepId = array_key_first($matches);
            foreach ($matches as $id => $repository) {
                if ($repository->getUrl() === $url) {
                    $keepId = $id;
                    break;
                }
            }
            $keep = $matches[$keepId];
            if ($keep->getUrl() !== $url) {
                $changes[] = sprintf('repositories: respelled %s as %s', $keep->getUrl(), $url);
                $respelled = new Repository($url, $keep->getType());
                if ($keep instanceof Repository) {
                    $respelled->setInstallationSource($keep->getInstallationSource());
                }
                $repositories->offsetUnset($keepId);
                $repositories->offsetSet($respelled->getId(), $respelled);
            }
            foreach ($matches as $id => $repository) {
                if ($id !== $keepId) {
                    $changes[] = 'repositories: removed duplicate ' . $repository->getUrl();
                    $repositories->offsetUnset($id);
                }
            }
        }

        $require = $config->getRequire() ?? [];
        if (!self::contains($require, $package)) {
            $require[] = new PackageConstraint($package, '*');
            $config->setRequire($require);
            $changes[] = 'require: added ' . $package;
        }

        return $changes;
    }

    /**
     * Whether a list of constraints names a package, ignoring case as Composer does.
     *
     * @param array<int|string, mixed>|null $constraints
     */
    public static function contains(?array $constraints, string $package): bool
    {
        foreach ($constraints ?? [] as $name => $constraint) {
            $candidate = $constraint instanceof PackageConstraint ? $constraint->getPackage() : (string) $name;
            if (0 === strcasecmp($candidate, $package)) {
                return true;
            }
        }

        return false;
    }

    /**
     * A repository URL's identity, "host/path" in lower case, without scheme,
     * credentials, .git or a trailing slash. GitLab routes paths case-insensitively.
     */
    public static function projectKey(string $url): ?string
    {
        $url = trim($url);
        if (preg_match('#^(?:https?|ssh|git)://(?:[^@/]+@)?([^/:]+)(?::\d+)?/(.+)$#i', $url, $matches)
            || preg_match('#^[^@/:]+@([^:/]+):(.+)$#', $url, $matches)) {
            $path = trim((string) preg_replace('#(?:\.git)?/*$#i', '', $matches[2]), '/');

            return '' === $path ? null : strtolower($matches[1] . '/' . $path);
        }

        return null;
    }
}
