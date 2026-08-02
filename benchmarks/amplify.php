<?php

/**
 * Amplifies a real NDJSON dataset by an integer factor for large-scale
 * benchmarks: writes each original record followed by (factor - 1) mutated
 * copies, preserving the real field distributions.
 *
 * Usage:
 *   php benchmarks/amplify.php --factor=10 \
 *       [--in=benchmarks/data/models-full.ndjson] \
 *       [--out=benchmarks/data/models-amplified.ndjson]
 *
 * Mutations per copy n (1..factor-1):
 *   id          original id + "~n" suffix (keeps PK uniqueness)
 *   author      random draw from a sample pool of real authors
 *   downloads   original ±20% uniform noise
 *   likes       original ±20% uniform noise
 *   created_ts  original shifted back by a uniform 0..365 days
 *   tags, pipeline_tag, library_name   copied verbatim
 *
 * Both input and output are streamed line by line; memory stays flat except
 * for the author pool sample.
 */

declare(strict_types=1);

const AUTHOR_POOL_SIZE = 50000;
const PROGRESS_STEP = 1_000_000;

$options = getopt('', ['factor:', 'in::', 'out::']);
$factor = max(2, (int) ($options['factor'] ?? 10));
$inFile = (string) ($options['in'] ?? __DIR__ . '/data/models-full.ndjson');
$outFile = (string) ($options['out'] ?? __DIR__ . '/data/models-amplified.ndjson');

if (!is_file($inFile)) {
    fwrite(STDERR, "Input file $inFile not found. Run: composer bench-seed -- --count=all --out=$inFile\n");
    exit(1);
}

// Pass 1: reservoir-sample a pool of real authors.
$pool = [];
$seenAuthors = 0;
$in = fopen($inFile, 'rb');
while (($line = fgets($in)) !== false) {
    $record = json_decode($line, true);
    if (!is_array($record) || ($record['author'] ?? '') === '') {
        continue;
    }
    $seenAuthors++;
    if (count($pool) < AUTHOR_POOL_SIZE) {
        $pool[] = $record['author'];
    } else {
        $j = mt_rand(0, $seenAuthors - 1);
        if ($j < AUTHOR_POOL_SIZE) {
            $pool[$j] = $record['author'];
        }
    }
}
fclose($in);

if ($pool === []) {
    fwrite(STDERR, "No authors found in $inFile\n");
    exit(1);
}
$poolSize = count($pool);
printf("Author pool: %d sampled from %d records with authors\n", $poolSize, $seenAuthors);

// Pass 2: stream originals + mutated copies.
$in = fopen($inFile, 'rb');
$out = fopen($outFile, 'wb');
if ($out === false) {
    fwrite(STDERR, "Cannot open $outFile for writing\n");
    exit(1);
}

$written = 0;
$nextProgressAt = PROGRESS_STEP;
$startedAt = microtime(true);

/** ±20% uniform noise, never negative */
function noise(int $value): int
{
    return max(0, (int) round($value * (0.8 + mt_rand(0, 400_000) / 1_000_000)));
}

while (($line = fgets($in)) !== false) {
    $line = trim($line);
    if ($line === '') {
        continue;
    }
    $record = json_decode($line, true);
    if (!is_array($record) || ($record['id'] ?? '') === '') {
        continue;
    }

    fwrite($out, $line . "\n");
    $written++;

    for ($n = 1; $n < $factor; $n++) {
        $copy = $record;
        $copy['id'] = $record['id'] . '~' . $n;
        $copy['author'] = $pool[mt_rand(0, $poolSize - 1)];
        $copy['downloads'] = noise((int) $record['downloads']);
        $copy['likes'] = noise((int) $record['likes']);
        if ((int) $record['created_ts'] > 0) {
            $copy['created_ts'] = (int) $record['created_ts'] - mt_rand(0, 365 * 86400);
        }
        fwrite($out, json_encode($copy, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
        $written++;
    }

    if ($written >= $nextProgressAt) {
        $elapsed = microtime(true) - $startedAt;
        printf("%d records written (%.0fs, %.0f rec/s)\n", $written, $elapsed, $written / max($elapsed, 0.001));
        $nextProgressAt += PROGRESS_STEP;
    }
}

fclose($in);
fclose($out);

printf(
    "Done: %d records (factor %d) written to %s in %.0fs\n",
    $written,
    $factor,
    $outFile,
    microtime(true) - $startedAt
);
