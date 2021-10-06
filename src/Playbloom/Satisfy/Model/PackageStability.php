<?php

namespace Playbloom\Satisfy\Model;

class PackageStability
{
    /** @var string */
    private $package;

    /** @var string */
    private $stability;

    public function __construct(string $package, string $stability)
    {
        $this->package = $package;
        $this->stability = $stability;
    }

    public function getPackage(): string
    {
        return $this->package;
    }

    public function setPackage(string $package): void
    {
        $this->package = $package;
    }

    public function getStability(): string
    {
        return $this->stability;
    }

    public function setStability(string $stability): void
    {
        $this->stability = $stability;
    }
}
