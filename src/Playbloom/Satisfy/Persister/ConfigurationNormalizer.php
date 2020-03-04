<?php

namespace Playbloom\Satisfy\Persister;

use Playbloom\Satisfy\Model\Configuration;
use Playbloom\Satisfy\Model\PackageConstraint;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;

class ConfigurationNormalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface
{
    /** @var SerializerInterface */
    private $serializer;

    public function normalize($object, string $format = null, array $context = [])
    {
        return $object;
    }

    public function supportsNormalization($data, string $format = null)
    {
        return false;
    }

    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        if ($type === PackageConstraint::class . '[]') {
            return $this->denormalizeRequire($data);
        }

        if ($this->serializer instanceof DenormalizerInterface) {
            return $this->serializer->denormalize($data, $type, $format, $context);
        }

        return $data;
    }

    public function supportsDenormalization($data, string $type, string $format = null)
    {
        switch ($type) {
            case PackageConstraint::class . '[]':
                return true;
            default:
        }

        return false;
    }

    public function setSerializer(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    private function normalizeRequire($list)
    {
        $require = [];
        foreach ($list as $constraint) {
            $require[$constraint->getPackage()] = $constraint->getConstraint();
        }

        return $require;
    }

    private function denormalizeRequire($data)
    {
        $require = [];
        foreach ($data as $package => $constraint) {
            $require[] = new PackageConstraint($package, $constraint);
        }

        return $require;
    }
}
