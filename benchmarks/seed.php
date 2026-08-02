<?php

/**
 * Downloads the Hugging Face Hub model catalog into an NDJSON file used by the
 * benchmark suite (see docs/benchmarks.md).
 *
 * Usage:
 *   php benchmarks/seed.php [--count=20000] [--out=benchmarks/data/models.ndjson]
 *   php benchmarks/seed.php --count=all   # full catalog, until the cursor is exhausted
 *
 * Uses the public API https://huggingface.co/api/models with cursor pagination
 * (the cursor comes back in the Link response header). Retries on 429/5xx.
 *
 * Records are written to the NDJSON file as they arrive; memory stays flat
 * regardless of the target size. Duplicate ids are possible across page
 * boundaries and are deduplicated server-side by the primary key on load.
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Client;

const API_URL = 'https://huggingface.co/api/models';
const PAGE_LIMIT = 1000;
const MAX_RETRIES = 5;
const USER_AGENT = 'reindexer-client-bench/3.0 (+https://github.com/Smolevich/reindexer-client)';

$options = getopt('', ['count::', 'out::']);
$countOption = (string) ($options['count'] ?? '20000');
$target = $countOption === 'all' ? PHP_INT_MAX : max(1, (int) $countOption);
$outFile = (string) ($options['out'] ?? __DIR__ . '/data/models.ndjson');

$outDir = dirname($outFile);
if (!is_dir($outDir) && !mkdir($outDir, 0777, true) && !is_dir($outDir)) {
    fwrite(STDERR, "Cannot create directory $outDir\n");
    exit(1);
}

$client = new Client([
    'headers' => ['User-Agent' => USER_AGENT],
    'timeout' => 60,
    'http_errors' => false,
]);

/**
 * @return array{0: \Psr\Http\Message\ResponseInterface}
 */
function fetchPage(Client $client, string $url): \Psr\Http\Message\ResponseInterface
{
    $attempt = 0;
    while (true) {
        $response = $client->get($url);
        $status = $response->getStatusCode();

        if ($status === 200) {
            return $response;
        }

        $attempt++;
        if ($attempt > MAX_RETRIES || ($status !== 429 && $status < 500)) {
            fwrite(STDERR, "HF API request failed with HTTP $status after $attempt attempt(s): $url\n");
            exit(1);
        }

        $retryAfter = (int) ($response->getHeaderLine('Retry-After') ?: 0);
        $delay = $retryAfter > 0 ? $retryAfter : 2 ** $attempt;
        fwrite(STDERR, "HTTP $status, retrying in {$delay}s (attempt $attempt/" . MAX_RETRIES . ")\n");
        sleep($delay);
    }
}

function nextUrlFromLinkHeader(string $link): ?string
{
    if ($link !== '' && preg_match('/<([^>]+)>;\s*rel="next"/', $link, $m)) {
        return $m[1];
    }

    return null;
}

/**
 * Maps a raw HF API model entry to the benchmark record shape.
 *
 * @param array<string, mixed> $raw
 * @return array<string, mixed>|null null when the entry has no usable id
 */
function mapModel(array $raw): ?array
{
    $id = (string) ($raw['modelId'] ?? $raw['id'] ?? '');
    if ($id === '') {
        return null;
    }

    $author = (string) ($raw['author'] ?? '');
    if ($author === '' && str_contains($id, '/')) {
        $author = explode('/', $id, 2)[0];
    }

    $createdTs = 0;
    if (!empty($raw['createdAt'])) {
        $parsed = strtotime((string) $raw['createdAt']);
        if ($parsed !== false) {
            $createdTs = $parsed;
        }
    }

    $tags = [];
    foreach ((array) ($raw['tags'] ?? []) as $tag) {
        if (is_string($tag) && $tag !== '') {
            $tags[] = $tag;
        }
    }

    return [
        'id' => $id,
        'author' => $author,
        'downloads' => (int) ($raw['downloads'] ?? 0),
        'likes' => (int) ($raw['likes'] ?? 0),
        'pipeline_tag' => isset($raw['pipeline_tag']) ? (string) $raw['pipeline_tag'] : null,
        'library_name' => isset($raw['library_name']) ? (string) $raw['library_name'] : null,
        'tags' => $tags,
        'created_ts' => $createdTs,
    ];
}

$out = fopen($outFile, 'wb');
if ($out === false) {
    fwrite(STDERR, "Cannot open $outFile for writing\n");
    exit(1);
}

$written = 0;
$page = 0;
$nextProgressAt = 100_000;
$url = API_URL . '?' . http_build_query([
    'limit' => min(PAGE_LIMIT, $target),
    'sort' => 'downloads',
    'full' => 'false',
]);

$startedAt = microtime(true);

while ($url !== null && $written < $target) {
    $response = fetchPage($client, $url);
    $page++;

    $models = json_decode((string) $response->getBody(), true);
    if (!is_array($models)) {
        fwrite(STDERR, "Unexpected payload on page $page\n");
        exit(1);
    }

    if ($models === []) {
        break;
    }

    foreach ($models as $raw) {
        if (!is_array($raw)) {
            continue;
        }
        $record = mapModel($raw);
        if ($record === null) {
            continue;
        }
        fwrite($out, json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
        $written++;
        if ($written >= $target) {
            break;
        }
    }

    if ($written >= $nextProgressAt || $written >= $target) {
        $elapsed = microtime(true) - $startedAt;
        printf(
            "page %d: %d records (%.0fs, %.0f rec/s)\n",
            $page,
            $written,
            $elapsed,
            $written / max($elapsed, 0.001)
        );
        $nextProgressAt += 100_000;
    }

    $url = nextUrlFromLinkHeader($response->getHeaderLine('Link'));
}

fclose($out);

printf("Done: %d records written to %s in %.1fs\n", $written, $outFile, microtime(true) - $startedAt);

if ($countOption !== 'all' && $written < $target) {
    fwrite(STDERR, "Warning: API exhausted before reaching target ($written < $target)\n");
}
