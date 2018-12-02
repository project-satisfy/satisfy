<?php

namespace Playbloom\Satisfy\Model;

class PackageConstraint
{
    /** @var string */
    private $package;

    /** @var string */
    private $constraint;

    public function __construct(string $package, string $constraint)
    {
        $this->package = $package;
        $this->constraint = $constraint;
    }

    public function getPackage(): string
    {
        return $this->package;
    }

    /**
     * @return $this
     */
    public function setPackage(string $package)
    {
        $this->package = $package;

        return $this;
    }

    public function getConstraint(): string
    {
        return $this->constraint;
    }

    /**
     * @return $this
     */
    public function setConstraint(string $constraint)
    {
        $this->constraint = $constraint;

        return $this;
    }
}
