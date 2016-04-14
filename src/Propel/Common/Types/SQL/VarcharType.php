<?php

namespace Propel\Common\Types\SQL;

use Propel\Common\Types\AbstractType;
use Propel\Runtime\Map\FieldMap;

class VarcharType extends AbstractType
{
//    public function convertToPHPValue($value, FieldMap $fieldMap)
//    {
//        return (string) $value;
//    }

    public function databaseToProperty($value, FieldMap $fieldMap)
    {
        return (string) $value;
    }

    public function propertyToDatabase($value, FieldMap $fieldMap)
    {
        return (string) $value;
    }
}