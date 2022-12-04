<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: reindexer.proto

namespace Reindexer\Grpc;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * JSON Schema definition.
 *
 * Generated from protobuf message <code>reindexer.grpc.SchemaDefinition</code>
 */
class SchemaDefinition extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>string nsName = 1;</code>
     */
    protected $nsName = '';
    /**
     * Generated from protobuf field <code>string jsonData = 2;</code>
     */
    protected $jsonData = '';

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $nsName
     *     @type string $jsonData
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Reindexer::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>string nsName = 1;</code>
     * @return string
     */
    public function getNsName()
    {
        return $this->nsName;
    }

    /**
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
     * Generated from protobuf field <code>string jsonData = 2;</code>
     * @return string
     */
    public function getJsonData()
    {
        return $this->jsonData;
    }

    /**
     * Generated from protobuf field <code>string jsonData = 2;</code>
     * @param string $var
     * @return $this
     */
    public function setJsonData($var)
    {
        GPBUtil::checkString($var, True);
        $this->jsonData = $var;

        return $this;
    }

}
