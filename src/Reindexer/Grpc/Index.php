<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: reindexer.proto

namespace Reindexer\Grpc;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Index definition.
 *
 * Generated from protobuf message <code>reindexer.grpc.Index</code>
 */
class Index extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>string name = 1;</code>
     */
    protected $name = '';
    /**
     * Generated from protobuf field <code>repeated string jsonPaths = 2;</code>
     */
    private $jsonPaths;
    /**
     * Generated from protobuf field <code>string indexType = 3;</code>
     */
    protected $indexType = '';
    /**
     * Generated from protobuf field <code>string fieldType = 4;</code>
     */
    protected $fieldType = '';
    /**
     * Generated from protobuf field <code>.reindexer.grpc.IndexOptions options = 5;</code>
     */
    protected $options = null;
    /**
     * TTL (in seconds)
     *
     * Generated from protobuf field <code>int64 expireAfter = 6;</code>
     */
    protected $expireAfter = 0;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $name
     *     @type array<string>|\Google\Protobuf\Internal\RepeatedField $jsonPaths
     *     @type string $indexType
     *     @type string $fieldType
     *     @type \Reindexer\Grpc\IndexOptions $options
     *     @type int|string $expireAfter
     *           TTL (in seconds)
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Reindexer::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>string name = 1;</code>
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Generated from protobuf field <code>string name = 1;</code>
     * @param string $var
     * @return $this
     */
    public function setName($var)
    {
        GPBUtil::checkString($var, True);
        $this->name = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>repeated string jsonPaths = 2;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getJsonPaths()
    {
        return $this->jsonPaths;
    }

    /**
     * Generated from protobuf field <code>repeated string jsonPaths = 2;</code>
     * @param array<string>|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setJsonPaths($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::STRING);
        $this->jsonPaths = $arr;

        return $this;
    }

    /**
     * Generated from protobuf field <code>string indexType = 3;</code>
     * @return string
     */
    public function getIndexType()
    {
        return $this->indexType;
    }

    /**
     * Generated from protobuf field <code>string indexType = 3;</code>
     * @param string $var
     * @return $this
     */
    public function setIndexType($var)
    {
        GPBUtil::checkString($var, True);
        $this->indexType = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>string fieldType = 4;</code>
     * @return string
     */
    public function getFieldType()
    {
        return $this->fieldType;
    }

    /**
     * Generated from protobuf field <code>string fieldType = 4;</code>
     * @param string $var
     * @return $this
     */
    public function setFieldType($var)
    {
        GPBUtil::checkString($var, True);
        $this->fieldType = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>.reindexer.grpc.IndexOptions options = 5;</code>
     * @return \Reindexer\Grpc\IndexOptions|null
     */
    public function getOptions()
    {
        return $this->options;
    }

    public function hasOptions()
    {
        return isset($this->options);
    }

    public function clearOptions()
    {
        unset($this->options);
    }

    /**
     * Generated from protobuf field <code>.reindexer.grpc.IndexOptions options = 5;</code>
     * @param \Reindexer\Grpc\IndexOptions $var
     * @return $this
     */
    public function setOptions($var)
    {
        GPBUtil::checkMessage($var, \Reindexer\Grpc\IndexOptions::class);
        $this->options = $var;

        return $this;
    }

    /**
     * TTL (in seconds)
     *
     * Generated from protobuf field <code>int64 expireAfter = 6;</code>
     * @return int|string
     */
    public function getExpireAfter()
    {
        return $this->expireAfter;
    }

    /**
     * TTL (in seconds)
     *
     * Generated from protobuf field <code>int64 expireAfter = 6;</code>
     * @param int|string $var
     * @return $this
     */
    public function setExpireAfter($var)
    {
        GPBUtil::checkInt64($var);
        $this->expireAfter = $var;

        return $this;
    }

}
