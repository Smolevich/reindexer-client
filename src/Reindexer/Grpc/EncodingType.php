<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: reindexer.proto

namespace Reindexer\Grpc;

use UnexpectedValueException;

/**
 * Data encoding types.
 *
 * Protobuf type <code>reindexer.grpc.EncodingType</code>
 */
class EncodingType
{
    /**
     * Generated from protobuf enum <code>JSON = 0;</code>
     */
    const JSON = 0;
    /**
     * Generated from protobuf enum <code>CJSON = 1;</code>
     */
    const CJSON = 1;
    /**
     * Generated from protobuf enum <code>MSGPACK = 2;</code>
     */
    const MSGPACK = 2;
    /**
     * Generated from protobuf enum <code>PROTOBUF = 3;</code>
     */
    const PROTOBUF = 3;

    private static $valueToName = [
        self::JSON => 'JSON',
        self::CJSON => 'CJSON',
        self::MSGPACK => 'MSGPACK',
        self::PROTOBUF => 'PROTOBUF',
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
