<?php

namespace Playbloom\Satisfy\Model;

use JMS\Serializer\Annotation\Type;

/**
 * Repository class
 *
 * Represent a composer repository definition
 *
 * @author Ludovic Fleury <ludo.fleury@gmail.com>
 */
class Repository implements RepositoryInterface
{
    /**
     * @Type("string")
     */
    private $type;

    /**
     * @Type("string")
     */
    private $url;

    /**
     * Initialize with default opinionated values
     */
    public function __construct()
    {
        $this->type = 'git';
        $this->url = '';
    }

    /**
     * Get the string representation
     *
     * @return string
     */
    public function __toString()
    {
        return $this->url;
    }

    /**
     * Get identifier
     *
     * @return string
     */
    public function getId()
    {
        return md5($this->getUrl());
    }

    /**
     * Get type
     *
     * @return string $type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set type
     *
     * @param string $type
     *
     * @return static
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get url
     *
     * @return string $url
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set url
     *
     * @param string $url
     *
     * @return static
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }
}
