<?php

namespace Playbloom\Plugin\Diagnosis\Tool;

use Playbloom\Service\LogRedactor;
use Playbloom\Service\Manager;
use Symfony\AI\Agent\Toolbox\Attribute\AsTool;

/**
 * What satis.json asks Satis to build, without its "config" section, which
 * can hold credentials.
 *
 * It takes an argument so that its schema has parameters: some endpoints,
 * Hugging Face's router among them, refuse a tool whose schema has none.
 */
#[AsTool('satis_repositories', 'The repositories in satis.json with their types and URLs, its require list, and whether require-all is on. Credentials in URLs are replaced with ***.')]
final class SatisRepositories
{
    public function __construct(private readonly Manager $manager)
    {
    }

    /**
     * @param string $contains only the repositories whose URL contains this text, such as a vendor or project name; empty for every repository
     *
     * @return array{repositories: list<array{type: string, url: string}>, require: array<string, string>, require_all: bool}
     */
    public function __invoke(string $contains = ''): array
    {
        $repositories = [];
        foreach ($this->manager->getRepositories() as $repository) {
            // Matched against the redacted URL, so that a credential cannot be
            // read back one guessed substring at a time.
            $url = LogRedactor::redact($repository->getUrl());
            if ('' === $contains || false !== stripos($url, $contains)) {
                $repositories[] = ['type' => $repository->getType(), 'url' => $url];
            }
        }
        $config = $this->manager->getConfig();
        $require = [];
        foreach ($config->getRequire() ?? [] as $constraint) {
            $require[$constraint->getPackage()] = $constraint->getConstraint();
        }

        return [
            'repositories' => $repositories,
            'require' => $require,
            'require_all' => $config->isRequireAll(),
        ];
    }
}
