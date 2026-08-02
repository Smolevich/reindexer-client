<?php

declare(strict_types=1);

namespace Reindexer\Benchmarks\Engines\Support;

use Reindexer\Benchmarks\Support\HfModels;

/**
 * Shared definitions for the engine-comparison benchmarks
 * (Reindexer vs Elasticsearch vs Typesense vs Meilisearch), see
 * docs/benchmarks-engines.md.
 *
 * All engines index the same NDJSON dataset and answer the same six query
 * scenarios; the constants here keep the scenario parameters identical.
 */
final class EngineDataset
{
    public const COLLECTION = 'hf_models';
    public const DEFAULT_DATA_FILE = __DIR__ . '/../../data/models-full.ndjson';

    /** Scenario parameters, shared by every engine bench. */
    public const FULLTEXT_TERMS = ['bert', 'llama'];
    public const POPULAR_TAG = 'llama';
    public const FILTER_DOWNLOADS = 100_000;
    public const FILTER_PIPELINE_TAG = 'text-generation';
    public const FACET_FILTER_DOWNLOADS = 100_000;
    public const DATE_RANGE_DAYS = 90;
    public const RESULT_LIMIT = 100;
    public const POINT_LOOKUP_IDS = 1000;

    public static function dataFile(): string
    {
        $file = getenv('BENCH_DATA_FILE');

        return $file !== false && $file !== '' ? $file : self::DEFAULT_DATA_FILE;
    }

    /**
     * Document key for engines that restrict primary-key characters:
     * Meilisearch allows only [a-zA-Z0-9_-] and Typesense forbids characters
     * that need URL encoding, while real HF ids contain '/' and '.'.
     * md5 is deterministic, collision-free in practice and computable from the
     * original id at lookup time.
     */
    public static function uid(string $id): string
    {
        return md5($id);
    }

    /**
     * The first $count primary keys of the dataset file — the same documents
     * are point-looked-up on every engine.
     *
     * @return string[]
     */
    public static function sampleIds(int $count = self::POINT_LOOKUP_IDS): array
    {
        $ids = [];
        foreach (HfModels::readNdjson(self::dataFile(), $count) as $line) {
            $doc = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
            $ids[] = (string) $doc['id'];
        }
        if (count($ids) < $count) {
            throw new \RuntimeException(sprintf(
                'Data file %s has only %d records, %d required',
                self::dataFile(),
                count($ids),
                $count
            ));
        }

        return $ids;
    }

    public static function dateFrom(): int
    {
        return time() - self::DATE_RANGE_DAYS * 86400;
    }

    public static function requireEnv(string $name): string
    {
        $value = (string) getenv($name);
        if ($value === '') {
            throw new \RuntimeException("$name is not set (use benchmarks/docker-compose-engines.yml)");
        }

        return $value;
    }
}
