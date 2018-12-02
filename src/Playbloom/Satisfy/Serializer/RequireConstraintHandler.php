<?php

namespace Playbloom\Satisfy\Serializer;

use JMS\Serializer\Context;
use JMS\Serializer\JsonSerializationVisitor;
use JMS\Serializer\VisitorInterface;
use Playbloom\Satisfy\Model\PackageConstraint;

class RequireConstraintHandler
{
    public function serializeCollection(VisitorInterface $visitor, array $collection, array $type, Context $context)
    {
        if (empty($collection)) {
            return null;
        }

        if ($visitor instanceof JsonSerializationVisitor) {
            $visitor->setOptions(JSON_PRETTY_PRINT);
        }

        $data = [];
        /** @var PackageConstraint $constraint */
        foreach ($collection as $constraint) {
            $data[$constraint->getPackage()] = $constraint->getConstraint();
        }

        return $data;
    }

    public function deserializeCollection(VisitorInterface $visitor, $data, array $type, Context $context)
    {
        if (empty($data)) {
            return [];
        }

        $constraints = [];
        foreach ($data as $package => $constraint) {
            if (empty($package)) {
                continue;
            }
            if (empty($constraint)) {
                $constraint = '*';
            }
            $constraints[] = new PackageConstraint($package, $constraint);
        }

        return $constraints;
    }
}
