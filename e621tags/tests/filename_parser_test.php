<?php

declare(strict_types=1);

require_once __DIR__ . '/../lib/FilenameParser.php';

use OCA\E621Tags\FilenameParser;

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

    // Regression cases for IDs embedded in descriptive filenames.
    'DateinameID10293-(1).png' => 10293,
    'DateinameID10293-(2).png' => 10293,
    'ID10293-(1).png' => 10293,
    'Dateiname-ID10293-(1).png' => 10293,
    'DateinameID10293-1.png' => 10293,
];

foreach ($cases as $filename => $expected) {
    $actual = $parser->extractPostId($filename);

    if ($actual !== $expected) {
        echo "[FAIL] {$filename}\n";
        echo '       Expected: ' . var_export($expected, true) . "\n";
        echo '       Actual:   ' . var_export($actual, true) . "\n";
        exit(1);
    }

    echo "[PASS] {$filename} => " .
        var_export($actual, true) .
        "\n";
}

echo "Filename parser test: OK\n";
