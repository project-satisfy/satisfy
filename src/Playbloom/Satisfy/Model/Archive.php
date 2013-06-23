<?php

namespace Playbloom\Satisfy\Model;

use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\SerializedName;

/**
 * Archive Configuration class
 *
 * Represent the archive part, in a satis configuration file
 *
 * @author Julius Beckmann <php@h4cc.de>
 */
class Archive {

    /**
     * @var string
     * @Type("string")
     */
    private $directory;

    /**
     * @var string
     * @Type("string")
     */
    private $format;

    /**
     * @var string
     * @Type("string")
     * @SerializedName("prefix-url")
     */
    private $prefix_url;

    /**
     * @var boolean
     * @Type("boolean")
     * @SerializedName("skip-dev")
     */
    private $skip_dev= true;

    /**
     * @param string $directory
     */
    public function setDirectory($directory)
    {
        $this->directory = $directory;
    }

    /**
     * @return string
     */
    public function getDirectory()
    {
        return $this->directory;
    }

    /**
     * @param string $format
     */
    public function setFormat($format)
    {
        $this->format = $format;
    }

    /**
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * @param string $prefix_url
     */
    public function setPrefixUrl($prefix_url)
    {
        $this->prefix_url = $prefix_url;
    }

    /**
     * @return string
     */
    public function getPrefixUrl()
    {
        return $this->prefix_url;
    }

    /**
     * @param boolean $skip_dev
     */
    public function setSkipDev($skip_dev)
    {
        $this->skip_dev = $skip_dev;
    }

    /**
     * @return boolean
     */
    public function getSkipDev()
    {
        return $this->skip_dev;
    }





}