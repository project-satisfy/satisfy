<?php

namespace Playbloom\Satisfy\Service;

use Playbloom\Satisfy\Model\Repository;

/**
 * Processor for uploaded composer.lock files.
 *
 * @author Julius Beckmann <php@h4cc.de>
 */
class LockProcessor
{
    private Manager $manager;

    /**
     * Constructor
     */
    public function __construct(Manager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Adds repositories from composer.lock JSON file.
     */
    public function processFile(\SplFileObject $file): void
    {
        $content = $this->getComposerLockData($file);
        $this->addReposFromContent($content);
    }

    /**
     * Reads and decodes json from given file.
     */
    private function getComposerLockData(\SplFileObject $file)
    {
        $json = file_get_contents($file->getRealPath());

        return json_decode($json);
    }

    /**
     * Adds all repos from composer.lock data, even require-dev ones.
     */
    private function addReposFromContent(\stdClass $content): void
    {
        $repositories = [];
        if (!empty($content->packages)) {
            $repositories = $this->getRepositories($content->packages);
        }

        if (!empty($content->{'packages-dev'})) {
            $repositories = array_merge($repositories, $this->getRepositories($content->{'packages-dev'}));
        }

        $this->manager->addAll($repositories);
    }

    /**
     * @return Repository[]
     */
    protected function getRepositories(array $packages): array
    {
        $repos = [];

        foreach ($packages as $package) {
            if (empty($package->source)) {
                continue;
            }
            $source = $package->source;
            if (!empty($source->url) && !empty($source->type)) {
                $repo = new Repository();
                $repo
                    ->setUrl($source->url)
                    ->setType($source->type);
                $repos[] = $repo;
            }
        }

        return $repos;
    }
}
