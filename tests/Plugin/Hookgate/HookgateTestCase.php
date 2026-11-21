<?php

namespace Playbloom\Tests\Plugin\Hookgate;

use Playbloom\Plugin\Hookgate\HookgateConfig;
use Playbloom\Plugin\Hookgate\HookgateLog;
use Playbloom\Plugin\Hookgate\Http;
use Playbloom\Plugin\Hookgate\PackageTypes;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Fakes for GitLab and drupal.org, and a log that records decisions.
 */
abstract class HookgateTestCase extends KernelTestCase
{
    /** @var array<int, array{decision: string, fields: array<string, mixed>}> */
    protected array $logged = [];

    protected function createConfig(string $mode = 'on'): HookgateConfig
    {
        return new HookgateConfig('token', 'https://git.example.com/api/v4', $mode, PackageTypes::DEFAULT, null, 'https://drupal.example/p2');
    }

    /**
     * Answers each API path by the first key it starts with, and 404 otherwise.
     *
     * @param array<string, array{0: int, 1: string}> $responses
     */
    protected function createHttp(HookgateConfig $config, array $responses, ?bool $drupalOrgServes = false, bool $token = true): Http
    {
        return new class($config, $responses, $drupalOrgServes, $token) extends Http {
            public function __construct(
                HookgateConfig $config,
                private readonly array $responses,
                private readonly ?bool $drupalOrg,
                private readonly bool $token,
            ) {
                parent::__construct($config);
            }

            public function hasApiToken(): bool
            {
                return $this->token;
            }

            public function api(string $projectUrl, string $pathAndQuery): array
            {
                foreach ($this->responses as $prefix => $response) {
                    if (str_starts_with($pathAndQuery, $prefix)) {
                        return $response;
                    }
                }

                return [404, ''];
            }

            public function drupalOrgServes(string $package): ?bool
            {
                return $this->drupalOrg;
            }
        };
    }

    protected function createLog(): HookgateLog
    {
        return new class(function (string $decision, array $fields): void {
            $this->logged[] = ['decision' => $decision, 'fields' => $fields];
        }) extends HookgateLog {
            public function __construct(private readonly \Closure $record)
            {
                parent::__construct('php://memory');
            }

            public function write(string $decision, array $fields = []): void
            {
                ($this->record)($decision, $fields);
            }
        };
    }

    /**
     * @return string[]
     */
    protected function decisions(): array
    {
        return array_column($this->logged, 'decision');
    }
}
