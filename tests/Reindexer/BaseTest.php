<?php

namespace Tests\Reindexer;

use PHPUnit\Framework\TestCase;
use Reindexer\Client\Api;
use Reindexer\Response;

abstract class BaseTest extends TestCase {
    public function createApiMock(array $methods) {
        return $this->getMockBuilder(Api::class)
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMock();
    }

    public function createApiResponseMock(array $methods) {
        return $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMock();
    }
}
