<?php

// Gagal (exit 1) bila line coverage pada laporan Clover di bawah ambang.
// Pemakaian: php scripts/coverage-check.php build/coverage/clover.xml 80

declare(strict_types=1);

[$script, $cloverPath, $threshold] = $argv + [null, 'build/coverage/clover.xml', '80'];

if (! is_file($cloverPath)) {
    fwrite(STDERR, "Laporan coverage tidak ditemukan: {$cloverPath}\n");
    exit(1);
}

$xml = simplexml_load_file($cloverPath);
$metrics = $xml?->project?->metrics;

if ($metrics === null) {
    fwrite(STDERR, "Format Clover tidak dikenali: {$cloverPath}\n");
    exit(1);
}

$statements = (int) $metrics['statements'];
$covered = (int) $metrics['coveredstatements'];
$coverage = $statements === 0 ? 100.0 : $covered / $statements * 100;
$minimum = (float) $threshold;

printf("Line coverage: %.2f%% (%d/%d), ambang minimum %.2f%%\n", $coverage, $covered, $statements, $minimum);

exit($coverage + 1e-9 < $minimum ? 1 : 0);
