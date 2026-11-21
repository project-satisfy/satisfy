<?php

namespace Playbloom\Queue;

use Playbloom\Event\BuildEvent;
use Playbloom\Model\BuildRequest;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Build requests waiting for the worker, one JSON file each.
 *
 * Files are written under a temporary name and renamed into place, so the
 * worker never reads half of one. A request for a repository already waiting
 * replaces it, so repeated deliveries for one push make one build.
 *
 * A request can be held back for some seconds. It waits under its own name, so
 * it neither replaces nor is replaced by an immediate request for the same
 * repository, and asking again for a held request starts its wait afresh.
 */
class BuildQueue
{
    private const FULL = 'full';
    private const PROCESSING = 'processing';
    private const LATER = 'later-';

    private Filesystem $filesystem;

    public function __construct(private readonly string $queueDir)
    {
        $this->filesystem = new Filesystem();
    }

    /**
     * Listener for BuildEvent: queue the build instead of running it.
     */
    public function onBuild(BuildEvent $event): void
    {
        $this->enqueue($event->getRepository()?->getUrl());
        $event->markQueued();
    }

    /**
     * Queues a build of one repository, or a full build when no URL is given,
     * to be claimed no sooner than $delay seconds from now.
     */
    public function enqueue(?string $repositoryUrl = null, int $delay = 0): void
    {
        $this->filesystem->mkdir($this->queueDir);

        $name = null === $repositoryUrl ? self::FULL : 'repo-' . sha1($repositoryUrl);
        $entry = ['repository' => $repositoryUrl, 'queued_at' => time()];
        if ($delay > 0) {
            $name = self::LATER . $name;
            $entry['not_before'] = $entry['queued_at'] + $delay;
        }
        $content = json_encode($entry, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        $temp = sprintf('%s/.%s.%s', $this->queueDir, $name, bin2hex(random_bytes(4)));
        if (false === @file_put_contents($temp, $content)) {
            throw new IOException(sprintf('Failed to write file "%s".', $temp), 0, null, $temp);
        }
        $this->filesystem->rename($temp, $this->queueDir . '/' . $name . '.json', true);
    }

    /**
     * Takes every waiting request that is due as one build, or returns null if
     * none is.
     *
     * @phpstan-impure
     */
    public function claim(): ?BuildRequest
    {
        $files = array_filter($this->pendingFiles(), $this->isDue(...));
        if (empty($files)) {
            return null;
        }

        $processing = $this->queueDir . '/' . self::PROCESSING;
        $this->filesystem->mkdir($processing);

        $full = false;
        $urls = [];
        $entries = [];
        foreach ($files as $file) {
            $entry = $processing . '/' . basename($file);
            if (!@rename($file, $entry)) {
                continue;
            }
            $entries[] = $entry;

            $data = json_decode((string) @file_get_contents($entry), true);
            $url = is_array($data) ? ($data['repository'] ?? null) : null;
            if (is_string($url) && '' !== $url) {
                $urls[] = $url;
            } else {
                $full = true;
            }
        }

        if (empty($entries)) {
            return null;
        }

        return new BuildRequest($full, array_values(array_unique($urls)), $entries);
    }

    /**
     * Removes the queue files a finished build was claimed from.
     */
    public function complete(BuildRequest $request): void
    {
        $this->filesystem->remove($request->getEntries());
    }

    /**
     * Returns requests a stopped worker had claimed to the queue.
     */
    public function recover(): void
    {
        $processing = $this->queueDir . '/' . self::PROCESSING;
        foreach (glob($processing . '/*.json') ?: [] as $entry) {
            $target = $this->queueDir . '/' . basename($entry);
            if (file_exists($target)) {
                $this->filesystem->remove($entry);
            } else {
                $this->filesystem->rename($entry, $target);
            }
        }
    }

    public function pending(): int
    {
        return count($this->pendingFiles());
    }

    public function isBuilding(): bool
    {
        return !empty(glob($this->queueDir . '/' . self::PROCESSING . '/*.json'));
    }

    /**
     * Whether a waiting request may be claimed now. One held back says when
     * in not_before; one that cannot be read is due, so it is not stuck.
     */
    private function isDue(string $file): bool
    {
        if (!str_starts_with(basename($file), self::LATER)) {
            return true;
        }
        $data = json_decode((string) @file_get_contents($file), true);
        $notBefore = is_array($data) ? ($data['not_before'] ?? null) : null;

        return !is_int($notBefore) || $notBefore <= time();
    }

    /**
     * @return string[]
     */
    private function pendingFiles(): array
    {
        return glob($this->queueDir . '/*.json') ?: [];
    }
}
