<?php

declare(strict_types=1);

require_once __DIR__ . '/../lib/E6aiTagParser.php';

use OCA\E621Tags\E6aiTagParser;

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

$expected = [
    'general' => ['blue_eyes'],
    'director' => ['test_director'],
    'character' => ['test_character'],
    'copyright' => ['test_franchise'],
    'species' => ['fox'],
    'invalid' => ['test_invalid'],
    'lore' => ['test_lore'],
    'meta' => ['test_meta'],
];

$actual = $parser->parse($post);

if ($actual !== $expected) {
    echo "[FAIL] e6AI tag groups were parsed incorrectly.\n";
    echo 'Expected: ' . var_export($expected, true) . "\n";
    echo 'Actual:   ' . var_export($actual, true) . "\n";
    exit(1);
}

echo "[PASS] All e6AI tag groups\n";

if ($parser->parseRating($post) !== 'rating:explicit') {
    echo "[FAIL] e6AI explicit rating\n";
    exit(1);
}

echo "[PASS] e6AI rating\n";

if (array_key_exists('contributor', $actual)) {
    echo "[FAIL] e6AI contributor must not be exposed as a common group\n";
    exit(1);
}

echo "[PASS] e6AI contributor handling\n";

$threw = false;

try {
    $parser->parse([]);
} catch (RuntimeException) {
    $threw = true;
}

if (!$threw) {
    echo "[FAIL] Missing e6AI tag data should throw RuntimeException\n";
    exit(1);
}

echo "[PASS] Missing e6AI tag data\n";
echo "e6AI tag parser test: OK\n";
