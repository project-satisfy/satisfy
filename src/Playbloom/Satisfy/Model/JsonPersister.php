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
    private $persister;
    private $satisClass;
    private $serializer;

    public function __construct(PersisterInterface $persister, SerializerInterface $serializer,  $satisClass)
    {
        $this->serializer = $serializer;
        $this->persister = $persister;
        $this->satisClass = $satisClass;
    }

    public function load()
    {
        $jsonString = $this->persister->load();
        return $this->serializer->deserialize($jsonString, $this->satisClass, 'json');
    }

    public function flush($object)
    {
        $jsonString = $this->serializer->serialize($object, 'json');
        $this->persister->flush($jsonString);
    }
}
