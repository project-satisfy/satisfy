<?php

namespace Playbloom\Plugin\Diagnosis;

/**
 * Where diagnoses are kept, and the OpenAI-compatible endpoint and model that
 * write them. Without an endpoint and a model, failed builds are not diagnosed.
 */
final class DiagnosisConfig
{
    public function __construct(
        private readonly string $dir,
        private readonly ?string $baseUrl = null,
        private readonly ?string $model = null,
        private readonly ?string $apiKey = null,
    ) {
    }

    public static function fromEnvironment(string $projectDir): self
    {
        return new self(
            self::env('SATISFY_DIAGNOSIS_DIR') ?? $projectDir . '/var/diagnosis',
            self::env('SATISFY_DIAGNOSIS_URL'),
            self::env('SATISFY_DIAGNOSIS_MODEL'),
            self::env('SATISFY_DIAGNOSIS_API_KEY'),
        );
    }

    public function isConfigured(): bool
    {
        return null !== $this->baseUrl && null !== $this->model;
    }

    public function getDir(): string
    {
        return $this->dir;
    }

    public function getBaseUrl(): ?string
    {
        return $this->baseUrl;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    private static function env(string $name): ?string
    {
        $value = $_SERVER[$name] ?? $_ENV[$name] ?? getenv($name);

        return is_string($value) && '' !== $value ? $value : null;
    }
}
