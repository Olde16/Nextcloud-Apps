<?php

declare(strict_types=1);

require_once __DIR__ . '/../lib/ApiRateLimiter.php';

use OCA\E621Tags\ApiRateLimiter;

function readRateLimitState(string $api): array {
    $safeApi = preg_replace('/[^a-z0-9_-]/i', '_', $api);

    if ($safeApi === null || $safeApi === '') {
        throw new RuntimeException('Could not build test state file name.');
    }

    $path = sys_get_temp_dir() . '/e621tags-ratelimit-' . $safeApi . '.json';

    if (!is_file($path)) {
        throw new RuntimeException('Rate limiter state file was not created.');
    }

    $contents = file_get_contents($path);

    if ($contents === false) {
        throw new RuntimeException('Could not read rate limiter state file.');
    }

    $state = json_decode($contents, true);

    if (!is_array($state)) {
        throw new RuntimeException('Rate limiter state is not valid JSON.');
    }

    return $state;
}

function cleanupRateLimitState(string $api): void {
    $safeApi = preg_replace('/[^a-z0-9_-]/i', '_', $api);

    if ($safeApi === null || $safeApi === '') {
        return;
    }

    $path = sys_get_temp_dir() . '/e621tags-ratelimit-' . $safeApi . '.json';

    if (is_file($path)) {
        unlink($path);
    }
}

$api = 'e621tags-test-' . getmypid() . '-' . bin2hex(random_bytes(4));
$limiter = new ApiRateLimiter();

try {
    $limiter->wait($api);

    $state = readRateLimitState($api);

    if ((float)$state['delay'] !== 1.0) {
        throw new RuntimeException('Initial rate limit delay should be 1 second.');
    }

    echo "[PASS] Initial delay\n";

    $limiter->backoff($api, 10);
    $state = readRateLimitState($api);

    if ((float)$state['delay'] !== 10.0) {
        throw new RuntimeException('Retry-After should increase the backoff delay.');
    }

    echo "[PASS] Retry-After backoff\n";

    $limiter->backoff($api, 999);
    $state = readRateLimitState($api);

    if ((float)$state['delay'] !== 60.0) {
        throw new RuntimeException('Rate limiter must not exceed the maximum delay.');
    }

    echo "[PASS] Maximum delay\n";

    $limiter->success($api);
    $state = readRateLimitState($api);

    if ((float)$state['delay'] !== 30.0) {
        throw new RuntimeException('Successful requests should reduce the backoff delay.');
    }

    echo "[PASS] Success recovery\n";
    echo "API rate limiter test: OK\n";
} catch (Throwable $e) {
    echo "[FAIL] API rate limiter\n";
    echo '       ' . $e->getMessage() . "\n";
    exit(1);
} finally {
    cleanupRateLimitState($api);
}
