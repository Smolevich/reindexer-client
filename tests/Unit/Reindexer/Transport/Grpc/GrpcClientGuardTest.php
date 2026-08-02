<?php

declare(strict_types=1);

namespace Tests\Unit\Reindexer\Transport\Grpc;

use PHPUnit\Framework\TestCase;
use Reindexer\Transport\Grpc\GrpcClient;
use RuntimeException;

final class GrpcClientGuardTest extends TestCase
{
    protected function setUp(): void
    {
        if (extension_loaded('grpc')) {
            $this->markTestSkipped('The "grpc" extension is loaded, the runtime guard cannot fire.');
        }
    }

    public function testAssertGrpcAvailableThrowsWithoutExtension(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('reindexer-client: gRPC transport requires the "grpc" PHP extension (pecl install grpc).');

        GrpcClient::assertGrpcAvailable();
    }

    public function testConstructorThrowsWithoutExtension(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('requires the "grpc" PHP extension');

        new GrpcClient();
    }
}
