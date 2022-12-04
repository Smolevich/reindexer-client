<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: reindexer.proto

namespace Reindexer\Grpc\IndexOptions;

use UnexpectedValueException;

/**
 * Protobuf type <code>reindexer.grpc.IndexOptions.RTreeType</code>
 */
class RTreeType
{
    /**
     * Generated from protobuf enum <code>Linear = 0;</code>
     */
    const Linear = 0;
    /**
     * Generated from protobuf enum <code>Quadratic = 1;</code>
     */
    const Quadratic = 1;
    /**
     * Generated from protobuf enum <code>Greene = 2;</code>
     */
    const Greene = 2;
    /**
     * Generated from protobuf enum <code>RStar = 3;</code>
     */
    const RStar = 3;

    private static $valueToName = [
        self::Linear => 'Linear',
        self::Quadratic => 'Quadratic',
        self::Greene => 'Greene',
        self::RStar => 'RStar',
    ];

    public static function name($value)
    {
        if (!isset(self::$valueToName[$value])) {
            throw new UnexpectedValueException(sprintf(
                    'Enum %s has no name defined for value %s', __CLASS__, $value));
        }
        return self::$valueToName[$value];
    }


    public static function value($name)
    {
        $const = __CLASS__ . '::' . strtoupper($name);
        if (!defined($const)) {
            throw new UnexpectedValueException(sprintf(
                    'Enum %s has no value defined for name %s', __CLASS__, $name));
        }
        return constant($const);
    }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(RTreeType::class, \Reindexer\Grpc\IndexOptions_RTreeType::class);
