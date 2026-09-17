<?php

declare(strict_types=1);

/**
 * Master test runner for the e621Tags test scripts.
 *
 * Each test is executed in its own PHP process so individual tests can
 * also be run directly and failures remain isolated.
 *
 * Run from the app directory with:
 *
 *     php tests/run.php
 */

$tests = [
    'filename_parser_test.php',
    'e621_tag_parser_test.php',
    'e6ai_tag_parser_test.php',
    'api_rate_limiter_test.php',
    'config_test.php',
    'queue_test.php',
    'file_tag_update_test.php',
    'db_test.php',
    'db_refresh_test.php',
    'db_validation_test.php',
];

$total = count($tests);
$passed = 0;
$failed = 0;

foreach ($tests as $test) {
    $path = __DIR__ . '/' . $test;

    echo "====================================\n";
    echo " {$test}\n";
    echo "====================================\n";

    if (!is_file($path)) {
        echo "[FAIL] Test file does not exist.\n\n";
        $failed++;
        continue;
    }

    $output = [];
    $exitCode = 0;

    $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($path);
    exec($command, $output, $exitCode);

    foreach ($output as $line) {
        echo $line . "\n";
    }

    if ($exitCode === 0) {
        $passed++;
        echo "[PASS] {$test}\n\n";
    } else {
        $failed++;
        echo "[FAIL] {$test} (exit code {$exitCode})\n\n";
    }
}

echo "====================================\n";
echo " Tests: {$total}\n";
echo " Passed: {$passed}\n";
echo " Failed: {$failed}\n";
echo "====================================\n";

if ($failed > 0) {
    echo "TEST SUITE FAILED\n";
    exit(1);
}

echo "ALL TESTS PASSED\n";
