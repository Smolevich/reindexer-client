<?php

declare(strict_types=1);

namespace Tests\Unit\Reindexer\Transport\Grpc;

use PHPUnit\Framework\TestCase;
use Reindexer\Transport\Grpc\GrpcClient;
use RuntimeException;

final class GrpcClientGuardTest extends TestCase
{
    private function skipIfGrpcLoaded(): void
    {
        if (extension_loaded('grpc')) {
            $this->markTestSkipped('The "grpc" extension is loaded, the runtime guard cannot fire.');
        }
    }

    public function testAssertGrpcAvailableThrowsWithoutExtension(): void
    {
        $this->skipIfGrpcLoaded();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('reindexer-client: gRPC transport requires the "grpc" PHP extension (pecl install grpc).');

        GrpcClient::assertGrpcAvailable();
    }

    public function testConstructorThrowsWithoutExtension(): void
    {
        $this->skipIfGrpcLoaded();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('requires the "grpc" PHP extension');

        new GrpcClient();
    }

    public function testMissingExtensionIsReportedFirst(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('reindexer-client: gRPC transport requires the "grpc" PHP extension (pecl install grpc).');

        GrpcClient::assertGrpcDependencies(false, false, false);
    }

    public function testMissingGrpcPackageIsReported(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('reindexer-client: gRPC transport requires composer packages grpc/grpc and google/protobuf.');

        GrpcClient::assertGrpcDependencies(true, false, true);
    }

    public function testMissingProtobufPackageIsReported(): void
    {
        // regression: the guard only checked grpc/grpc, a missing
        // google/protobuf slipped through to a fatal "class not found"
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('reindexer-client: gRPC transport requires composer packages grpc/grpc and google/protobuf.');

        GrpcClient::assertGrpcDependencies(true, true, false);
    }

    public function testAllDependenciesPresentPassesTheGuard(): void
    {
        GrpcClient::assertGrpcDependencies(true, true, true);

        $this->addToAssertionCount(1);
    }
}
