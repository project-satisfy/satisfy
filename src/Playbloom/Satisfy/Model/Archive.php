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
class Archive
{
    /**
     * @var string
     * @Type("string")
     */
    private $directory = '';

    /**
     * @var string
     * @Type("string")
     */
    private $format = 'tar';

    /**
     * @var string
     * @Type("string")
     * @SerializedName("absolute-directory")
     */
    private $absoluteDirectory = '';

    /**
     * @var boolean
     * @Type("boolean")
     * @SerializedName("skip-dev")
     */
    private $skipDev = true;

    /**
     * @var array
     * @Type("array")
     */
    private $whitelist = array();

    /**
     * @var array
     * @Type("array")
     */
    private $blacklist = array();

    /**
     * @var string
     * @Type("string")
     * @SerializedName("prefix-url")
     */
    private $prefixUrl = '';

    /**
     * @var boolean
     * @Type("boolean")
     */
    private $checksum = true;

    /**
     * @return string
     */
    public function getDirectory()
    {
        return $this->directory;
    }

    /**
     * @param string $directory
     * @return $this
     */
    public function setDirectory($directory)
    {
        $this->directory = $directory;

        return $this;
    }

    /**
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * @param string $format
     * @return $this
     */
    public function setFormat($format)
    {
        $this->format = $format;

        return $this;
    }

    /**
     * @return string
     */
    public function getAbsoluteDirectory()
    {
        return $this->absoluteDirectory;
    }

    /**
     * @param string $absoluteDirectory
     * @return $this
     */
    public function setAbsoluteDirectory($absoluteDirectory)
    {
        $this->absoluteDirectory = $absoluteDirectory;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isSkipDev()
    {
        return $this->skipDev;
    }

    /**
     * @param boolean $skipDev
     * @return $this
     */
    public function setSkipDev($skipDev)
    {
        $this->skipDev = (bool)$skipDev;

        return $this;
    }

    /**
     * @return array
     */
    public function getWhitelist()
    {
        return $this->whitelist;
    }

    /**
     * @param array $whitelist
     * @return $this
     */
    public function setWhitelist($whitelist)
    {
        $this->whitelist = $whitelist;

        return $this;
    }

    /**
     * @return array
     */
    public function getBlacklist()
    {
        return $this->blacklist;
    }

    /**
     * @param array $blacklist
     * @return $this
     */
    public function setBlacklist($blacklist)
    {
        $this->blacklist = $blacklist;

        return $this;
    }

    /**
     * @return string
     */
    public function getPrefixUrl()
    {
        return $this->prefixUrl;
    }

    /**
     * @param string $prefixUrl
     * @return $this
     */
    public function setPrefixUrl($prefixUrl)
    {
        $this->prefixUrl = $prefixUrl;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isChecksum()
    {
        return $this->checksum;
    }

    /**
     * @param boolean $checksum
     * @return $this
     */
    public function setChecksum($checksum)
    {
        $this->checksum = (bool)$checksum;

        return $this;
    }
}
