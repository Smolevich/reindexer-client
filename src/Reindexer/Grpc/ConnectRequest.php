<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: reindexer.proto

namespace Reindexer\Grpc;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Connection options.
 *
 * Generated from protobuf message <code>reindexer.grpc.ConnectRequest</code>
 */
class ConnectRequest extends \Google\Protobuf\Internal\Message
{
    /**
     * uri looks like `cproto://127.0.0.1:6534/var/lib/reindexer/dbname`
     * and consists of:
     *
     * Generated from protobuf field <code>string url = 1;</code>
     */
    protected $url = '';
    /**
     * Generated from protobuf field <code>string dbName = 2;</code>
     */
    protected $dbName = '';
    /**
     * Generated from protobuf field <code>string login = 3;</code>
     */
    protected $login = '';
    /**
     * Generated from protobuf field <code>string password = 4;</code>
     */
    protected $password = '';
    /**
     * Generated from protobuf field <code>.reindexer.grpc.ConnectOptions connectOpts = 5;</code>
     */
    protected $connectOpts = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $url
     *           uri looks like `cproto://127.0.0.1:6534/var/lib/reindexer/dbname`
     *           and consists of:
     *     @type string $dbName
     *     @type string $login
     *     @type string $password
     *     @type \Reindexer\Grpc\ConnectOptions $connectOpts
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Reindexer::initOnce();
        parent::__construct($data);
    }

    /**
     * uri looks like `cproto://127.0.0.1:6534/var/lib/reindexer/dbname`
     * and consists of:
     *
     * Generated from protobuf field <code>string url = 1;</code>
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * uri looks like `cproto://127.0.0.1:6534/var/lib/reindexer/dbname`
     * and consists of:
     *
     * Generated from protobuf field <code>string url = 1;</code>
     * @param string $var
     * @return $this
     */
    public function setUrl($var)
    {
        GPBUtil::checkString($var, True);
        $this->url = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>string dbName = 2;</code>
     * @return string
     */
    public function getDbName()
    {
        return $this->dbName;
    }

    /**
     * Generated from protobuf field <code>string dbName = 2;</code>
     * @param string $var
     * @return $this
     */
    public function setDbName($var)
    {
        GPBUtil::checkString($var, True);
        $this->dbName = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>string login = 3;</code>
     * @return string
     */
    public function getLogin()
    {
        return $this->login;
    }

    /**
     * Generated from protobuf field <code>string login = 3;</code>
     * @param string $var
     * @return $this
     */
    public function setLogin($var)
    {
        GPBUtil::checkString($var, True);
        $this->login = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>string password = 4;</code>
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Generated from protobuf field <code>string password = 4;</code>
     * @param string $var
     * @return $this
     */
    public function setPassword($var)
    {
        GPBUtil::checkString($var, True);
        $this->password = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>.reindexer.grpc.ConnectOptions connectOpts = 5;</code>
     * @return \Reindexer\Grpc\ConnectOptions|null
     */
    public function getConnectOpts()
    {
        return $this->connectOpts;
    }

    public function hasConnectOpts()
    {
        return isset($this->connectOpts);
    }

    public function clearConnectOpts()
    {
        unset($this->connectOpts);
    }

    /**
     * Generated from protobuf field <code>.reindexer.grpc.ConnectOptions connectOpts = 5;</code>
     * @param \Reindexer\Grpc\ConnectOptions $var
     * @return $this
     */
    public function setConnectOpts($var)
    {
        GPBUtil::checkMessage($var, \Reindexer\Grpc\ConnectOptions::class);
        $this->connectOpts = $var;

        return $this;
    }

}
