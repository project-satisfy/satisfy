<?php

namespace Playbloom\Satisfy\Persister;

use Playbloom\Satisfy\Model\Abandoned;
use Playbloom\Satisfy\Model\Configuration;
use Playbloom\Satisfy\Model\PackageConstraint;
use Playbloom\Satisfy\Model\PackageStability;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerAwareTrait;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Json definition file persister
 *
 * @author Ludovic Fleury <ludo.fleury@gmail.com>
 */
class JsonPersister implements PersisterInterface
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
        $jsonString = $this->serializer->serialize($object, 'json', [
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
        $this->persister->flush($jsonString);
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
