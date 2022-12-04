<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: reindexer.proto

namespace Reindexer\Grpc;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Metadata for Namespace.
 *
 * Generated from protobuf message <code>reindexer.grpc.Metadata</code>
 */
class Metadata extends \Google\Protobuf\Internal\Message
{
    /**
     * namespace name
     *
     * Generated from protobuf field <code>string nsName = 1;</code>
     */
    protected $nsName = '';
    /**
     * metadata key
     *
     * Generated from protobuf field <code>string key = 2;</code>
     */
    protected $key = '';
    /**
     * metadata, for Get operation this field is supposed to be empty
     *
     * Generated from protobuf field <code>string value = 3;</code>
     */
    protected $value = '';

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $nsName
     *           namespace name
     *     @type string $key
     *           metadata key
     *     @type string $value
     *           metadata, for Get operation this field is supposed to be empty
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Reindexer::initOnce();
        parent::__construct($data);
    }

    /**
     * namespace name
     *
     * Generated from protobuf field <code>string nsName = 1;</code>
     * @return string
     */
    public function getNsName()
    {
        return $this->nsName;
    }

    /**
     * namespace name
     *
     * Generated from protobuf field <code>string nsName = 1;</code>
     * @param string $var
     * @return $this
     */
    public function setNsName($var)
    {
        GPBUtil::checkString($var, True);
        $this->nsName = $var;

        return $this;
    }

    /**
     * metadata key
     *
     * Generated from protobuf field <code>string key = 2;</code>
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * metadata key
     *
     * Generated from protobuf field <code>string key = 2;</code>
     * @param string $var
     * @return $this
     */
    public function setKey($var)
    {
        GPBUtil::checkString($var, True);
        $this->key = $var;

        return $this;
    }

    /**
     * metadata, for Get operation this field is supposed to be empty
     *
     * Generated from protobuf field <code>string value = 3;</code>
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * metadata, for Get operation this field is supposed to be empty
     *
     * Generated from protobuf field <code>string value = 3;</code>
     * @param string $var
     * @return $this
     */
    public function setValue($var)
    {
        GPBUtil::checkString($var, True);
        $this->value = $var;

        return $this;
    }

}
