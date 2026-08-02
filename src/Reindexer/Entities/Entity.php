<?php

declare(strict_types=1);

namespace Reindexer\Entities;

use \ReflectionProperty;

abstract class Entity
{
    protected array $mapJsonFields = [];

    public function getBody(): array
    {
        return $this->parseValue($this);
    }

    protected function parseValue(object $instance): array
    {
        $result = [];
        $reflectionClass = new \ReflectionClass($instance);
        $properties = $reflectionClass->getProperties(ReflectionProperty::IS_PRIVATE);

        foreach ($properties as $property) {
            $property->setAccessible(true);
            $value = $property->getValue($instance);

            if (is_null($value)) {
                continue;
            }

            if ($value instanceof \BackedEnum) {
                $value = $value->value;
            } elseif (is_object($value)) {
                $value = $this->parseValue($value);
            }

            if (isset($this->mapJsonFields[$property->getName()])) {
                $name = $this->mapJsonFields[$property->getName()];
                $result[$name] = $value;
            }
        }

        return $result;
    }
}
