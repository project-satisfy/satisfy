<?php

namespace Playbloom\Persister;

use Playbloom\Model\Abandoned;
use Playbloom\Model\Configuration;
use Playbloom\Model\PackageConstraint;
use Playbloom\Model\PackageStability;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerAwareTrait;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Json definition file persister
 *
 * @author Ludovic Fleury <ludo.fleury@gmail.com>
 */
class JsonPersister implements LockablePersisterInterface
{
    use SerializerAwareTrait;

    /** @var PersisterInterface */
    private $persister;

    /** @var string */
    private $satisClass;

    public function __construct(PersisterInterface $persister, SerializerInterface $serializer, string $satisClass)
    {
        $this->setSerializer($serializer);
        $this->persister = $persister;
        $this->satisClass = $satisClass;
    }

    public function load(): Configuration
    {
        $jsonString = $this->persister->load();
        if ('' === trim($jsonString)) {
            throw new \RuntimeException('Satis file is empty.');
        }

        return $this->serializer->deserialize($jsonString, $this->satisClass, 'json');
    }

    public function flush($object): void
    {
        $this->persister->flush($this->serialize($object));
    }

    /**
     * The configuration as flush() would write it.
     */
    public function serialize(Configuration $object): string
    {
        return $this->serializer->serialize($object, 'json', [
            AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
            AbstractObjectNormalizer::CALLBACKS => [
                'repositories' => [$this, 'normalizeRepositories'],
                'require' => [$this, 'normalizeRequire'],
                'blacklist' => [$this, 'normalizeRequire'],
                'abandoned' => [$this, 'normalizeAbandoned'],
                'minimumStabilityPerPackage' => [$this, 'normalizePackageStability'],
            ],
            JsonEncode::OPTIONS => JSON_PRETTY_PRINT,
        ]);
    }

    public function lock(): void
    {
        if ($this->persister instanceof LockablePersisterInterface) {
            $this->persister->lock();
        }
    }

    public function unlock(): void
    {
        if ($this->persister instanceof LockablePersisterInterface) {
            $this->persister->unlock();
        }
    }

    public function getVersion(): string
    {
        return $this->persister instanceof LockablePersisterInterface ? $this->persister->getVersion() : '';
    }

    public function normalizeRepositories($repositories): array
    {
        if ($repositories instanceof \ArrayIterator) {
            return array_values($repositories->getArrayCopy());
        }

        return [];
    }

    /**
     * @param PackageConstraint[]|null $constraints
     *
     * @return string[]|null
     */
    public function normalizeRequire($constraints)
    {
        if (empty($constraints)) {
            return null;
        }
        $require = [];
        foreach ($constraints as $constraint) {
            $require[$constraint->getPackage()] = $constraint->getConstraint();
        }

        return $require;
    }

    /**
     * @param Abandoned[]|null $abandoned
     *
     * @return array<string, bool|string>|null
     */
    public function normalizeAbandoned($abandoned)
    {
        if (empty($abandoned)) {
            return null;
        }
        $list = [];
        foreach ($abandoned as $package) {
            $replacement = $package->getReplacement();
            if (empty($replacement)) {
                $replacement = true;
            }
            $list[$package->getPackage()] = $replacement;
        }

        return $list;
    }

    /**
     * @param PackageStability[] $list
     *
     * @return array<string, string>|null
     */
    public function normalizePackageStability(array $list): ?array
    {
        if (empty($list)) {
            return null;
        }

        $data = [];
        foreach ($list as $item) {
            $data[$item->getPackage()] = $item->getStability();
        }

        return $data;
    }
}
