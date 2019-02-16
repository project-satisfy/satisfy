<?php

namespace Playbloom\Satisfy\Persister;

use JMS\Serializer\SerializerInterface;
use Playbloom\Satisfy\Model\Configuration;

/**
 * Json definition file persister
 *
 * @author Ludovic Fleury <ludo.fleury@gmail.com>
 */
class JsonPersister implements PersisterInterface
{
    /** @var PersisterInterface */
    private $persister;

    /** @var string */
    private $satisClass;

    /** @var SerializerInterface */
    private $serializer;

    /**
     * JsonPersister constructor.
     */
    public function __construct(PersisterInterface $persister, SerializerInterface $serializer, string $satisClass)
    {
        $this->serializer = $serializer;
        $this->persister = $persister;
        $this->satisClass = $satisClass;
    }

    /**
     * @return object
     */
    public function load()
    {
        $jsonString = $this->persister->load();
        if ('' === trim($jsonString)) {
            throw new \RuntimeException('Satis file is empty.');
        }

        /** @var Configuration $configuration */
        $configuration = $this->serializer->deserialize($jsonString, $this->satisClass, 'json');

        return $configuration;
    }

    /**
     * @param \stdClass $object
     */
    public function flush($object)
    {
        $jsonString = $this->serializer->serialize($object, 'json');
        $this->persister->flush($jsonString);
    }
}
