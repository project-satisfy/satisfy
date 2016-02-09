<?php

namespace Playbloom\Satisfy\Models;

use JMS\Serializer\SerializerInterface;

class JsonPersister implements PersisterInterface
{
    /**
     * @var \Playbloom\Satisfy\Models\PersisterInterface
     */
    private $persister;

    /**
     * @var \JMS\Serializer\SerializerInterface
     */
    private $serializer;

    /**
     * @var string
     */
    private $satisClass;

    /**
     * Bind instance to the class.
     *
     * @param  \Playbloom\Satisfy\Models\PersisterInterface   $persister
     * @param  \JMS\Serializer\SerializerInterface            $serializer
     * @param  string                                         $satisClass
     */
    public function __construct(
        PersisterInterface  $persister,
        SerializerInterface $serializer,
        $satisClass
    ) {
        $this->persister = $persister;

        $this->serializer = $serializer;

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

        return $this->serializer->deserialize(
            $jsonString,
            $this->satisClass,
            'json'
        );
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
