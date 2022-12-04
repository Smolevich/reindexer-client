<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: reindexer.proto

namespace Reindexer\Grpc;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Transaction Item message
 *
 * Generated from protobuf message <code>reindexer.grpc.AddTxItemRequest</code>
 */
class AddTxItemRequest extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>int64 id = 1;</code>
     */
    protected $id = 0;
    /**
     * mode
     *
     * Generated from protobuf field <code>.reindexer.grpc.ModifyMode mode = 2;</code>
     */
    protected $mode = 0;
    /**
     * encoding type
     *
     * Generated from protobuf field <code>.reindexer.grpc.EncodingType encodingType = 3;</code>
     */
    protected $encodingType = 0;
    /**
     * data buffer
     *
     * Generated from protobuf field <code>bytes data = 4;</code>
     */
    protected $data = '';

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type int|string $id
     *     @type int $mode
     *           mode
     *     @type int $encodingType
     *           encoding type
     *     @type string $data
     *           data buffer
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Reindexer::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>int64 id = 1;</code>
     * @return int|string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Generated from protobuf field <code>int64 id = 1;</code>
     * @param int|string $var
     * @return $this
     */
    public function setId($var)
    {
        GPBUtil::checkInt64($var);
        $this->id = $var;

        return $this;
    }

    /**
     * mode
     *
     * Generated from protobuf field <code>.reindexer.grpc.ModifyMode mode = 2;</code>
     * @return int
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * mode
     *
     * Generated from protobuf field <code>.reindexer.grpc.ModifyMode mode = 2;</code>
     * @param int $var
     * @return $this
     */
    public function setMode($var)
    {
        GPBUtil::checkEnum($var, \Reindexer\Grpc\ModifyMode::class);
        $this->mode = $var;

        return $this;
    }

    /**
     * encoding type
     *
     * Generated from protobuf field <code>.reindexer.grpc.EncodingType encodingType = 3;</code>
     * @return int
     */
    public function getEncodingType()
    {
        return $this->encodingType;
    }

    /**
     * encoding type
     *
     * Generated from protobuf field <code>.reindexer.grpc.EncodingType encodingType = 3;</code>
     * @param int $var
     * @return $this
     */
    public function setEncodingType($var)
    {
        GPBUtil::checkEnum($var, \Reindexer\Grpc\EncodingType::class);
        $this->encodingType = $var;

        return $this;
    }

    /**
     * data buffer
     *
     * Generated from protobuf field <code>bytes data = 4;</code>
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * data buffer
     *
     * Generated from protobuf field <code>bytes data = 4;</code>
     * @param string $var
     * @return $this
     */
    public function setData($var)
    {
        GPBUtil::checkString($var, False);
        $this->data = $var;

        return $this;
    }

}
