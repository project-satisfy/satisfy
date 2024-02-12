<?php

namespace Playbloom\Satisfy\Persister;

use Playbloom\Satisfy\Model\Abandoned;
use Playbloom\Satisfy\Model\PackageConstraint;
use Playbloom\Satisfy\Model\PackageStability;
use Playbloom\Satisfy\Model\Repository;
use Playbloom\Satisfy\Model\RepositoryInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;

class ConfigurationNormalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface
{
    /** @var SerializerInterface */
    private $serializer;

    public function normalize($object, ?string $format = null, array $context = [])
    {
        return $object;
    }

    public function supportsNormalization($data, ?string $format = null)
    {
        return false;
    }

    public function denormalize($data, string $type, ?string $format = null, array $context = [])
    {
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

    public function supportsDenormalization($data, string $type, ?string $format = null)
    {
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

    public function setSerializer(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
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
