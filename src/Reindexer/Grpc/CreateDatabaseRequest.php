<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: reindexer.proto

namespace Reindexer\Grpc;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\GPBUtil;
use Google\Protobuf\Internal\RepeatedField;

/**
 * Generated from protobuf message <code>reindexer.grpc.CreateDatabaseRequest</code>
 */
class CreateDatabaseRequest extends \Google\Protobuf\Internal\Message {
    /**
     * Generated from protobuf field <code>string dbName = 1;</code>
     */
    protected $dbName = '';

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $dbName
     * }
     */
    public function __construct($data = null) {
        \GPBMetadata\Reindexer::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>string dbName = 1;</code>
     * @return string
     */
    public function getDbName() {
        return $this->dbName;
    }

    /**
     * Generated from protobuf field <code>string dbName = 1;</code>
     * @param string $var
     * @return $this
     */
    public function setDbName($var) {
        GPBUtil::checkString($var, true);
        $this->dbName = $var;

        return $this;
    }
}
