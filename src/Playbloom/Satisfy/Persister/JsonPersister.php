<?php

namespace Playbloom\Satisfy\Persister;

use JMS\Serializer\SerializerInterface;

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
     * @param PersisterInterface $persister
     * @param SerializerInterface $serializer
     * @param string $satisClass
     */
    public function __construct(PersisterInterface $persister, SerializerInterface $serializer, $satisClass)
    {
        $this->serializer = $serializer;
        $this->persister = $persister;
        $this->satisClass = $satisClass;
    }

    /**
     * @return \stdClass
     */
    public function load()
    {
        $jsonString = $this->persister->load();
        if ('' === trim($jsonString)) {
            throw new \RuntimeException('Satis file is empty.');
        }
        return $this->serializer->deserialize($jsonString, $this->satisClass, 'json');
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
