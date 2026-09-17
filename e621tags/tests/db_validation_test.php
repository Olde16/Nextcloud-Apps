<?php

declare(strict_types=1);

define('OC_CONSOLE', true);

require '/var/www/nextcloud/lib/base.php';

use OCA\E621Tags\Db\ApiDataService;
use OCP\Server;

$service = Server::get(ApiDataService::class);

function expectInvalidArgument(
    callable $callback,
    string $name,
): void {
    try {
        $callback();
    } catch (InvalidArgumentException) {
        echo "[PASS] {$name}\n";
        return;
    }

    echo "[FAIL] {$name}: expected InvalidArgumentException\n";
    exit(1);
}

function expectRuntimeException(
    callable $callback,
    string $name,
): void {
    try {
        $callback();
    } catch (RuntimeException) {
        echo "[PASS] {$name}\n";
        return;
    }

    echo "[FAIL] {$name}: expected RuntimeException\n";
    exit(1);
}

$validPost = [
    'rating' => 's',
    'tags' => [
        'general' => ['test'],
    ],
];

expectInvalidArgument(
    static fn() => $service->savePost(
        'unknown',
        1,
        1,
        $validPost,
    ),
    'savePost rejects unsupported source'
);

expectInvalidArgument(
    static fn() => $service->savePost(
        'e621',
        0,
        1,
        $validPost,
    ),
    'savePost rejects invalid file ID'
);

expectInvalidArgument(
    static fn() => $service->savePost(
        'e621',
        1,
        0,
        $validPost,
    ),
    'savePost rejects invalid post ID'
);

expectRuntimeException(
    static fn() => $service->savePost(
        'e621',
        1,
        1,
        ['tags' => 'invalid'],
    ),
    'savePost rejects invalid tag data'
);

expectInvalidArgument(
    static fn() => $service->refreshPost(
        'unknown',
        1,
        $validPost,
    ),
    'refreshPost rejects unsupported source'
);

expectInvalidArgument(
    static fn() => $service->refreshPost(
        'e621',
        0,
        $validPost,
    ),
    'refreshPost rejects invalid post ID'
);

expectRuntimeException(
    static fn() => $service->refreshPost(
        'e621',
        999999993,
        $validPost,
    ),
    'refreshPost rejects unknown post records'
);

expectInvalidArgument(
    static fn() => $service->markChecked(0),
    'markChecked rejects invalid record ID'
);

expectInvalidArgument(
    static fn() => $service->linkFile(0, 1),
    'linkFile rejects invalid post record ID'
);

expectInvalidArgument(
    static fn() => $service->linkFile(1, 0),
    'linkFile rejects invalid file ID'
);

$empty = $service->getPostsForUpdate([], 10);

if ($empty !== []) {
    echo "[FAIL] Empty source list should return no update records\n";
    exit(1);
}

echo "[PASS] Empty source list\n";

$empty = $service->getPostsForUpdate(['e621'], 0);

if ($empty !== []) {
    echo "[FAIL] Non-positive update limit should return no records\n";
    exit(1);
}

echo "[PASS] Non-positive update limit\n";

expectInvalidArgument(
    static fn() => $service->getPostsForUpdate(['unknown'], 10),
    'getPostsForUpdate rejects unsupported source'
);

echo "Database validation test: OK\n";
