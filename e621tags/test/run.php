<?php

declare(strict_types=1);

/**
 * Small, dependency-free regression test suite for e621Tags.
 *
 * Run from the app directory with:
 *
 *     php test/run.php
 *
 * The tests intentionally stay simple and focus on deterministic logic.
 */

require_once __DIR__ . '/../lib/FilenameParser.php';
require_once __DIR__ . '/../lib/E621TagParser.php';
require_once __DIR__ . '/../lib/E6aiTagParser.php';
require_once __DIR__ . '/../lib/ApiRateLimiter.php';

use OCA\E621Tags\ApiRateLimiter;
use OCA\E621Tags\E621TagParser;
use OCA\E621Tags\E6aiTagParser;
use OCA\E621Tags\FilenameParser;

$total = 0;
$passed = 0;
$failed = 0;

function assertSameValue(
    mixed $expected,
    mixed $actual,
    string $message,
): void {
    if ($expected !== $actual) {
        throw new RuntimeException(
            $message .
            "\nExpected: " .
            var_export($expected, true) .
            "\nActual: " .
            var_export($actual, true)
        );
    }
}

function assertTrue(bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function runTest(
    string $name,
    callable $test,
): void {
    global $total, $passed, $failed;

    $total++;

    try {
        $test();
        $passed++;
        echo "[PASS] {$name}\n";
    } catch (Throwable $e) {
        $failed++;
        echo "[FAIL] {$name}\n";
        echo "       {$e->getMessage()}\n";
    }
}

function readRateLimitState(string $api): array {
    $safeApi = preg_replace(
        '/[^a-z0-9_-]/i',
        '_',
        $api
    );

    if ($safeApi === null || $safeApi === '') {
        throw new RuntimeException('Could not build test state file name.');
    }

    $path = sys_get_temp_dir() .
        '/e621tags-ratelimit-' .
        $safeApi .
        '.json';

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
    $safeApi = preg_replace(
        '/[^a-z0-9_-]/i',
        '_',
        $api
    );

    if ($safeApi === null || $safeApi === '') {
        return;
    }

    $path = sys_get_temp_dir() .
        '/e621tags-ratelimit-' .
        $safeApi .
        '.json';

    if (is_file($path)) {
        unlink($path);
    }
}

echo "====================================\n";
echo " e621Tags Regression Tests\n";
echo "====================================\n\n";

runTest(
    'Filename parser',
    static function (): void {
        $parser = new FilenameParser();

        $cases = [
            '123456.jpg' => 123456,
            'image_123456.png' => 123456,
            '123456_artwork.webp' => 123456,
            'foo123456bar.jpg' => 123456,
            '00123.jpg' => 123,
            '123456789012.png' => 123456789012,
            '1234567890123.png' => null,
            'artwork.jpg' => null,
        ];

        foreach ($cases as $filename => $expected) {
            assertSameValue(
                $expected,
                $parser->extractPostId($filename),
                'Unexpected post ID for ' . $filename
            );
        }
    }
);

runTest(
    'e621 tag parser',
    static function (): void {
        $parser = new E621TagParser();

        $post = [
            'rating' => 'q',
            'tags' => [
                'general' => ['blue_eyes', 'tail', 123],
                'artist' => ['test_artist'],
                'character' => ['test_character'],
                'copyright' => ['test_copyright'],
                'species' => ['fox'],
                'invalid' => [],
                'lore' => ['test_lore'],
                'meta' => ['test_meta'],
            ],
        ];

        assertSameValue(
            [
                'general' => ['blue_eyes', 'tail'],
                'artist' => ['test_artist'],
                'character' => ['test_character'],
                'copyright' => ['test_copyright'],
                'species' => ['fox'],
                'invalid' => [],
                'lore' => ['test_lore'],
                'meta' => ['test_meta'],
            ],
            $parser->parse($post),
            'e621 tag groups were parsed incorrectly.'
        );

        assertSameValue(
            'rating:questionable',
            $parser->parseRating($post),
            'e621 questionable rating was parsed incorrectly.'
        );

        assertSameValue(
            null,
            $parser->parseRating(['rating' => 'unknown']),
            'Unknown e621 ratings should return null.'
        );

        assertSameValue(
            [],
            $parser->parse(['tags' => ['general' => ['only_general']]])['artist'],
            'Missing e621 tag groups should return an empty list.'
        );

        $threw = false;

        try {
            $parser->parse([]);
        } catch (RuntimeException) {
            $threw = true;
        }

        assertTrue(
            $threw,
            'e621 parser should reject posts without tag data.'
        );
    }
);

runTest(
    'e6AI tag parser',
    static function (): void {
        $parser = new E6aiTagParser();

        $post = [
            'rating' => 'e',
            'tags' => [
                'general' => ['blue_eyes'],
                'director' => ['test_director'],
                'character' => ['test_character'],
                'franchise' => ['test_franchise'],
                'species' => ['fox'],
                'invalid' => ['test_invalid'],
                'lore' => ['test_lore'],
                'meta' => ['test_meta'],
                'contributor' => ['should_not_be_exposed'],
            ],
        ];

        assertSameValue(
            [
                'general' => ['blue_eyes'],
                'director' => ['test_director'],
                'character' => ['test_character'],
                'copyright' => ['test_franchise'],
                'species' => ['fox'],
                'invalid' => ['test_invalid'],
                'lore' => ['test_lore'],
                'meta' => ['test_meta'],
            ],
            $parser->parse($post),
            'e6AI tag groups were parsed incorrectly.'
        );

        assertSameValue(
            'rating:explicit',
            $parser->parseRating($post),
            'e6AI explicit rating was parsed incorrectly.'
        );

        assertTrue(
            !array_key_exists('contributor', $parser->parse($post)),
            'Contributor must not be exposed as a common tag group.'
        );

        $threw = false;

        try {
            $parser->parse([]);
        } catch (RuntimeException) {
            $threw = true;
        }

        assertTrue(
            $threw,
            'e6AI parser should reject posts without tag data.'
        );
    }
);

runTest(
    'API rate limiter',
    static function (): void {
        $api = 'e621tags-test-' .
            getmypid() . '-' .
            bin2hex(random_bytes(4));

        $limiter = new ApiRateLimiter();

        try {
            $limiter->wait($api);

            $state = readRateLimitState($api);
            assertSameValue(
                1.0,
                (float)$state['delay'],
                'Initial rate limit delay should be 1 second.'
            );

            $limiter->backoff($api, 10);

            $state = readRateLimitState($api);
            assertSameValue(
                10.0,
                (float)$state['delay'],
                'Retry-After should increase the backoff delay.'
            );

            $limiter->backoff($api, 999);

            $state = readRateLimitState($api);
            assertSameValue(
                60.0,
                (float)$state['delay'],
                'Rate limiter must not exceed the maximum delay.'
            );

            $limiter->success($api);

            $state = readRateLimitState($api);
            assertSameValue(
                30.0,
                (float)$state['delay'],
                'Successful requests should reduce the backoff delay.'
            );
        } finally {
            cleanupRateLimitState($api);
        }
    }
);

echo "\n====================================\n";
echo " Tests: {$total}\n";
echo " Passed: {$passed}\n";
echo " Failed: {$failed}\n";
echo "====================================\n";

if ($failed > 0) {
    echo "TEST SUITE FAILED\n";
    exit(1);
}

echo "ALL TESTS PASSED\n";
