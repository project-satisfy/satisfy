<?php

namespace Playbloom\Satisfy\Model;

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
     * @return object
     */
    public function load()
    {
        $jsonString = $this->persister->load();
        if ('' == trim($jsonString)) {
            throw new \InvalidArgumentException("The used satis file is empty. Create using this site 'http://getcomposer.org/doc/articles/handling-private-packages-with-satis.md'.");
        }
        return $this->serializer->deserialize($jsonString, $this->satisClass, 'json');
    }

    /**
     * @param object $object
     */
    public function flush($object)
    {
        $jsonString = $this->serializer->serialize($object, 'json');
        $this->persister->flush($jsonString);
    }
}
