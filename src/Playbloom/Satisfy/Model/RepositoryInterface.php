<?php

namespace Playbloom\Satisfy\Model;

interface RepositoryInterface
{
    /**
     * Get unique identifier.
     */
    public function getId(): string;

    /**
     * Get repository label.
     */
    public function getLabel(): string;

    /**
     * Get the repository type
     */
    public function getType(): string;

    /**
     * Set repository type.
     */
    public function setType(string $type): self;

    /**
     * Get the repository host/url
     */
    public function getUrl();

    /**
     * Set repository host/url.
     */
    public function setUrl($url): self;

    /**
     * Set/reset repository package.
     */
    public function setPackage($package): self;

    /**
     * Returns the package type, e.g. library
     *
     * @return string The package type
     */
    public function getPackageType(): string;

    /**
     * Returns the package's name without version info, thus not a unique identifier
     *
     * @return string package name
     */
    public function getPackageName();

    /**
     * Set the package's name without version info, thus not a unique identifier
     *
     * @param string $name
     *
     * @return \Playbloom\Satisfy\Model\RepositoryInterface
     */
    public function setPackageName($name);

    /**
     * Returns the package's version
     *
     * @return string package name
     */
    public function getPackageVersion();

    /**
     * Set the package's version
     *
     * @param string $version
     *
     * @return \Playbloom\Satisfy\Model\RepositoryInterface
     */
    public function setPackageVersion($version);

    /**
     * Returns the sha1 checksum for the distribution archive of this version
     *
     * @return string
     */
    public function getPackageDistSha1Checksum();

    /**
     * Set the sha1 checksum for the distribution archive of this version
     *
     * @return \Playbloom\Satisfy\Model\RepositoryInterface
     */
    public function setPackageDistSha1Checksum($sha1): self;

    /**
     * Returns the type of the distribution archive of this version, e.g. zip, tarball
     *
     * @return string The repository type
     */
    public function getPackageDistType(): string;

    /**
     * Set the repository package type.
     *
     * @param string $type
     *
     * @return \Playbloom\Satisfy\Model\RepositoryInterface
     */
    public function setPackageDistType($type);

    /**
     * Returns the url of the distribution archive of this version
     */
    public function getPackageDistUrl(): string;

    /**
     * Set the repository package dist host/url.
     *
     * @param string $url
     *
     * @return \Playbloom\Satisfy\Model\RepositoryInterface
     */
    public function setPackageDistUrl($url);

//    /**
//     * Returns the repository type of this package, e.g. git, svn
//     *
//     * @return string The repository type
//     */
//    public function getPackageSourceType(): string;
//
//    /**
//     * Set dist type.
//     */
//    public function setPackageSourceType(string $type): self;
//
//    /**
//     * Returns the repository url of this package, e.g. git://github.com/naderman/composer.git
//     *
//     * @return string The repository package url
//     */
//    public function getPackageSourceUrl(): string;
//
//    /**
//     * Set the repository package source url.
//     *
//     * @param string $url
//     *
//     * @return \Playbloom\Satisfy\Model\RepositoryInterface
//     */
//    public function setPackageSourceUrl(string $url): self;
}
