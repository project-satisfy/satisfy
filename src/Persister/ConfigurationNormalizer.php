<?php

namespace Playbloom\Persister;

use Playbloom\Model\Abandoned;
use Playbloom\Model\Configuration;
use Playbloom\Model\PackageConstraint;
use Playbloom\Model\PackageStability;
use Playbloom\Model\Repository;
use Playbloom\Model\RepositoryInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;

class ConfigurationNormalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface
{
    private const CONFIGURATION_DENORMALIZED = 'satisfy_configuration_denormalized';

    /** @var SerializerInterface */
    private $serializer;

    public function normalize(mixed $object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        throw new \LogicException('This normalizer only denormalizes.');
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return false;
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        // Repositories are built here rather than left to the object normalizer:
        // setRepositories() accepts several types, and which one the serializer
        // tries first is not something to rely on.
        if (Configuration::class === $type && $this->serializer instanceof DenormalizerInterface) {
            $repositories = is_array($data) ? ($data['repositories'] ?? []) : [];
            if (is_array($data)) {
                unset($data['repositories']);
            }
            $context[self::CONFIGURATION_DENORMALIZED] = true;
            /** @var Configuration $configuration */
            $configuration = $this->serializer->denormalize($data, $type, $format, $context);
            $configuration->setRepositories($this->denormalizeRepositories($repositories));

            return $configuration;
        }

        if ($type === PackageConstraint::class . '[]') {
            return $this->denormalizeRequire($data);
        }

        if ($type === RepositoryInterface::class . '[]') {
            return $this->denormalizeRepositories($data);
        }

        if ($type === PackageStability::class . '[]') {
            return $this->denormalizePackageStability($data);
        }

        if ($type === Abandoned::class . '[]') {
            return $this->denormalizeAbandoned($data);
        }

        if ($this->serializer instanceof DenormalizerInterface) {
            return $this->serializer->denormalize($data, $type, $format, $context);
        }

        return $data;
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        if (Configuration::class === $type) {
            return empty($context[self::CONFIGURATION_DENORMALIZED]);
        }

        switch ($type) {
            case PackageConstraint::class . '[]':
            case RepositoryInterface::class . '[]':
            case PackageStability::class . '[]':
            case Abandoned::class . '[]':
                return true;
            default:
        }

        return false;
    }

    public function setSerializer(SerializerInterface $serializer): void
    {
        $this->serializer = $serializer;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            // Not cacheable: whether it applies depends on the context.
            Configuration::class => false,
            PackageConstraint::class . '[]' => true,
            RepositoryInterface::class . '[]' => true,
            PackageStability::class . '[]' => true,
            Abandoned::class . '[]' => true,
        ];
    }

    private function denormalizeRequire($data)
    {
        $require = [];
        foreach ($data as $package => $constraint) {
            $require[] = new PackageConstraint($package, $constraint);
        }

        return $require;
    }

    private function denormalizeRepositories($data)
    {
        $list = [];
        foreach ($data as $item) {
            $repository = new Repository($item['url'], $item['type']);
            if (!empty($item['installation-source'])) {
                $repository->setInstallationSource($item['installation-source']);
            }
            $list[$repository->getId()] = $repository;
        }

        return $list;
    }

    private function denormalizePackageStability($data): array
    {
        $list = [];
        foreach ($data as $package => $stability) {
            $list[] = new PackageStability($package, $stability);
        }

        return $list;
    }

    private function denormalizeAbandoned($data): array
    {
        $list = [];
        foreach ($data as $package => $replacement) {
            if (!is_string($replacement)) {
                $replacement = null;
            }
            $list[] = new Abandoned($package, $replacement);
        }

        return $list;
    }
}
