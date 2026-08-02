<?php

declare(strict_types=1);

namespace Reindexer\Benchmarks\Support;

use Reindexer\Client\Api;
use Reindexer\Transport\Grpc\GrpcClient;

/**
 * Shared definitions for the hf_models benchmark dataset: index schema for
 * both transports, namespace (re)creation and an NDJSON reader.
 */
final class HfModels
{
    public const DEFAULT_DB = 'bench_db';
    public const DEFAULT_NS = 'hf_models';
    public const DEFAULT_DATA_FILE = __DIR__ . '/../data/models.ndjson';

    /**
     * Index schema in reindexer HTTP API format (snake_case).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function httpIndexes(): array
    {
        return [
            ['name' => 'id', 'json_paths' => ['id'], 'field_type' => 'string', 'index_type' => 'hash', 'is_pk' => true],
            ['name' => 'downloads', 'json_paths' => ['downloads'], 'field_type' => 'int', 'index_type' => 'tree'],
            ['name' => 'likes', 'json_paths' => ['likes'], 'field_type' => 'int', 'index_type' => 'tree'],
            ['name' => 'pipeline_tag', 'json_paths' => ['pipeline_tag'], 'field_type' => 'string', 'index_type' => 'hash'],
            ['name' => 'library_name', 'json_paths' => ['library_name'], 'field_type' => 'string', 'index_type' => 'hash'],
            ['name' => 'tags', 'json_paths' => ['tags'], 'field_type' => 'string', 'index_type' => 'hash', 'is_array' => true],
            ['name' => 'created_ts', 'json_paths' => ['created_ts'], 'field_type' => 'int', 'index_type' => 'tree'],
            ['name' => 'id+author', 'json_paths' => ['id', 'author'], 'field_type' => 'composite', 'index_type' => 'text'],
        ];
    }

    /**
     * Same schema in the GrpcClient::addIndex() format (camelCase).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function grpcIndexes(): array
    {
        return array_map(
            static fn (array $index): array => array_filter([
                'name' => $index['name'],
                'jsonPaths' => $index['json_paths'],
                'fieldType' => $index['field_type'],
                'indexType' => $index['index_type'],
                'isPk' => $index['is_pk'] ?? false,
                'isArray' => $index['is_array'] ?? false,
            ], static fn ($v) => $v !== false),
            self::httpIndexes()
        );
    }

    /**
     * Drops (if present) and creates the namespace with the full schema via HTTP.
     */
    public static function recreateNamespaceHttp(Api $api, string $db, string $ns): void
    {
        $api->request('DELETE', "/api/v1/db/$db/namespaces/$ns");

        $body = [
            'name' => $ns,
            'storage' => ['enabled' => true],
            'indexes' => self::httpIndexes(),
        ];
        $response = $api->request(
            'POST',
            "/api/v1/db/$db/namespaces",
            json_encode($body, JSON_UNESCAPED_UNICODE),
            ['Content-Type' => 'application/json']
        );

        if ($response->getCode() !== 200) {
            throw new \RuntimeException(
                "Failed to create namespace $ns: HTTP {$response->getCode()} {$response->getResponseBody()}"
            );
        }
    }

    /**
     * Drops (if present) and creates the namespace with the full schema via gRPC.
     */
    public static function recreateNamespaceGrpc(GrpcClient $client, string $ns): void
    {
        try {
            $client->dropNamespace($ns);
        } catch (\Throwable) {
            // namespace did not exist
        }

        $client->openNamespace($ns);
        foreach (self::grpcIndexes() as $index) {
            $client->addIndex($ns, $index);
        }
    }

    /**
     * Creates the database over HTTP if it does not exist yet.
     */
    public static function ensureDatabaseHttp(Api $api, string $db): void
    {
        $response = $api->request(
            'POST',
            '/api/v1/db',
            json_encode(['name' => $db]),
            ['Content-Type' => 'application/json']
        );

        // 200 = created; anything else must mean "already exists"
        if ($response->getCode() !== 200 && !str_contains((string) $response->getResponseBody(), 'already exists')) {
            throw new \RuntimeException(
                "Failed to create database $db: HTTP {$response->getCode()} {$response->getResponseBody()}"
            );
        }
    }

    /**
     * Streams raw NDJSON lines (one JSON document per line).
     *
     * @return \Generator<int, string>
     */
    public static function readNdjson(string $file, int $limit = PHP_INT_MAX): \Generator
    {
        $handle = fopen($file, 'rb');
        if ($handle === false) {
            throw new \RuntimeException("Cannot open data file $file (run composer bench-seed first)");
        }

        try {
            $count = 0;
            while ($count < $limit && ($line = fgets($handle)) !== false) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }
                yield $line;
                $count++;
            }
        } finally {
            fclose($handle);
        }
    }
}
