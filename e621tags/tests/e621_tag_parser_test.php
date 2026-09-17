<?php

declare(strict_types=1);

require_once __DIR__ . '/../lib/E621TagParser.php';

use OCA\E621Tags\E621TagParser;

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

$expected = [
    'general' => ['blue_eyes', 'tail'],
    'artist' => ['test_artist'],
    'character' => ['test_character'],
    'copyright' => ['test_copyright'],
    'species' => ['fox'],
    'invalid' => [],
    'lore' => ['test_lore'],
    'meta' => ['test_meta'],
];

$actual = $parser->parse($post);

if ($actual !== $expected) {
    echo "[FAIL] e621 tag groups were parsed incorrectly.\n";
    echo 'Expected: ' . var_export($expected, true) . "\n";
    echo 'Actual:   ' . var_export($actual, true) . "\n";
    exit(1);
}

echo "[PASS] All e621 tag groups\n";

if ($parser->parseRating($post) !== 'rating:questionable') {
    echo "[FAIL] e621 questionable rating\n";
    exit(1);
}

echo "[PASS] e621 rating\n";

if ($parser->parseRating(['rating' => 'unknown']) !== null) {
    echo "[FAIL] Unknown e621 rating should return null\n";
    exit(1);
}

echo "[PASS] Unknown e621 rating\n";

$partial = $parser->parse([
    'tags' => [
        'general' => ['only_general'],
    ],
]);

if ($partial['artist'] !== []) {
    echo "[FAIL] Missing e621 groups should return empty lists\n";
    exit(1);
}

echo "[PASS] Missing e621 groups\n";

$threw = false;

try {
    $parser->parse([]);
} catch (RuntimeException) {
    $threw = true;
}

if (!$threw) {
    echo "[FAIL] Missing e621 tag data should throw RuntimeException\n";
    exit(1);
}

echo "[PASS] Missing e621 tag data\n";
echo "e621 tag parser test: OK\n";
