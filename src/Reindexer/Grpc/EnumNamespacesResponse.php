<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: reindexer.proto

namespace Reindexer\Grpc;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\GPBUtil;
use Google\Protobuf\Internal\RepeatedField;

/**
 * Generated from protobuf message <code>reindexer.grpc.EnumNamespacesResponse</code>
 */
class EnumNamespacesResponse extends \Google\Protobuf\Internal\Message {
    /**
     * Generated from protobuf field <code>repeated .reindexer.grpc.Namespace namespacesDefinitions = 1;</code>
     */
    private $namespacesDefinitions;
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
     *     @type array<\Reindexer\Grpc\PBNamespace>|\Google\Protobuf\Internal\RepeatedField $namespacesDefinitions
     *     @type \Reindexer\Grpc\ErrorResponse $errorResponse
     * }
     */
    public function __construct($data = null) {
        \GPBMetadata\Reindexer::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>repeated .reindexer.grpc.Namespace namespacesDefinitions = 1;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getNamespacesDefinitions() {
        return $this->namespacesDefinitions;
    }

    /**
     * Generated from protobuf field <code>repeated .reindexer.grpc.Namespace namespacesDefinitions = 1;</code>
     * @param array<\Reindexer\Grpc\PBNamespace>|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setNamespacesDefinitions($var) {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Reindexer\Grpc\PBNamespace::class);
        $this->namespacesDefinitions = $arr;

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
