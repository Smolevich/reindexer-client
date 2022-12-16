<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: reindexer.proto

namespace Reindexer\Grpc;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\GPBUtil;
use Google\Protobuf\Internal\RepeatedField;

/**
 * List of databases names
 *
 * Generated from protobuf message <code>reindexer.grpc.EnumDatabasesResponse</code>
 */
class EnumDatabasesResponse extends \Google\Protobuf\Internal\Message {
    /**
     * Generated from protobuf field <code>repeated string names = 1;</code>
     */
    private $names;
    /**
     * Generated from protobuf field <code>.reindexer.grpc.ErrorResponse errorResponse = 2;</code>
     */
    protected $errorResponse = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type array<string>|\Google\Protobuf\Internal\RepeatedField $names
     *     @type \Reindexer\Grpc\ErrorResponse $errorResponse
     * }
     */
    public function __construct($data = null) {
        \GPBMetadata\Reindexer::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>repeated string names = 1;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getNames() {
        return $this->names;
    }

    /**
     * Generated from protobuf field <code>repeated string names = 1;</code>
     * @param array<string>|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setNames($var) {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::STRING);
        $this->names = $arr;

        return $this;
    }

    /**
     * Generated from protobuf field <code>.reindexer.grpc.ErrorResponse errorResponse = 2;</code>
     * @return \Reindexer\Grpc\ErrorResponse|null
     */
    public function getErrorResponse() {
        return $this->errorResponse;
    }

    public function hasErrorResponse() {
        return isset($this->errorResponse);
    }

    public function clearErrorResponse() {
        unset($this->errorResponse);
    }

    /**
     * Generated from protobuf field <code>.reindexer.grpc.ErrorResponse errorResponse = 2;</code>
     * @param \Reindexer\Grpc\ErrorResponse $var
     * @return $this
     */
    public function setErrorResponse($var) {
        GPBUtil::checkMessage($var, \Reindexer\Grpc\ErrorResponse::class);
        $this->errorResponse = $var;

        return $this;
    }
}
