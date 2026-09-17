<?php

declare(strict_types=1);

define('OC_CONSOLE', true);

require '/var/www/nextcloud/lib/base.php';

use OCP\DB\IResult;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Server;
use OCA\E621Tags\Db\ApiDataService;

$source = 'e621';
$postId = 999999991;
$fileId = 999999991;

$db = Server::get(IDBConnection::class);
$service = Server::get(ApiDataService::class);

/**
 * Remove a test record and all dependent test data.
 */
function cleanupTestData(
    IDBConnection $db,
    string $source,
    int $postId,
    int $fileId,
): void {
    $qb = $db->getQueryBuilder();

    $qb->select('id')
        ->from('e621tags_posts')
        ->where(
            $qb->expr()->eq(
                'source',
                $qb->createNamedParameter(
                    $source,
                    IQueryBuilder::PARAM_STR
                )
            )
        )
        ->andWhere(
            $qb->expr()->eq(
                'post_id',
                $qb->createNamedParameter(
                    $postId,
                    IQueryBuilder::PARAM_INT
                )
            )
        )
        ->setMaxResults(1);

    $result = $qb->executeQuery();
    $recordId = $result->fetchOne();
    $result->closeCursor();

    if ($recordId === false) {
        return;
    }

    $recordId = (int)$recordId;

    $qb = $db->getQueryBuilder();

    $qb->delete('e621tags_tags')
        ->where(
            $qb->expr()->eq(
                'post_record_id',
                $qb->createNamedParameter(
                    $recordId,
                    IQueryBuilder::PARAM_INT
                )
            )
        )
        ->executeStatement();

    $qb = $db->getQueryBuilder();

    $qb->delete('e621tags_files')
        ->where(
            $qb->expr()->eq(
                'post_record_id',
                $qb->createNamedParameter(
                    $recordId,
                    IQueryBuilder::PARAM_INT
                )
            )
        )
        ->andWhere(
            $qb->expr()->eq(
                'file_id',
                $qb->createNamedParameter(
                    $fileId,
                    IQueryBuilder::PARAM_INT
                )
            )
        )
        ->executeStatement();

    $qb = $db->getQueryBuilder();

    $qb->delete('e621tags_posts')
        ->where(
            $qb->expr()->eq(
                'id',
                $qb->createNamedParameter(
                    $recordId,
                    IQueryBuilder::PARAM_INT
                )
            )
        )
        ->executeStatement();
}

echo "====================================\n";
echo " e621Tags DB Test\n";
echo "====================================\n";

echo "Cleaning up old test data...\n";

cleanupTestData(
    $db,
    $source,
    $postId,
    $fileId
);

$post = [
    'id' => $postId,
    'updated_at' => '2026-09-17T08:00:00.000+00:00',
    'change_seq' => 123456,
    'rating' => 's',

    'tags' => [
        'general' => [
            'blue_eyes',
            'tail',
        ],
        'artist' => [
            'test_artist',
        ],
        'character' => [
            'test_character',
        ],
        'copyright' => [
            'test_copyright',
        ],
        'species' => [
            'fox',
        ],
        'invalid' => [
            'test_invalid',
        ],
        'lore' => [
            'test_lore',
        ],
        'meta' => [
            'test_meta',
        ],
    ],
];

try {
    echo "Saving test record...\n";

    $recordId = $service->savePost(
        $source,
        $fileId,
        $postId,
        $post,
    );

    echo "Record ID: " . $recordId . "\n";

    echo "Reading post back...\n";

    $storedPost = $service->findPostByFileId(
        $fileId,
        $source
    );

    if ($storedPost === null) {
        throw new RuntimeException(
            'The stored post could not be found.'
        );
    }

    echo "Post stored: YES\n";

    echo "Post ID: " .
        (string)$storedPost['post_id'] .
        "\n";

    echo "Source: " .
        (string)$storedPost['source'] .
        "\n";

    echo "Change Sequence: " .
        (string)$storedPost['change_seq'] .
        "\n";

    echo "Rating: " .
        (string)$storedPost['rating'] .
        "\n";

    echo "Reading tags back...\n";

    $storedTags = $service->getTags(
        $recordId
    );

    echo "\nTag Groups:\n";

    foreach ($storedTags as $group => $tags) {
        echo "  " . $group . ": ";

        if ($tags === []) {
            echo "(empty)\n";
            continue;
        }

        echo implode(', ', $tags) . "\n";
    }

    $expectedGroups = [
        'general',
        'artist',
        'character',
        'copyright',
        'species',
        'invalid',
        'lore',
        'meta',
    ];

    foreach ($expectedGroups as $group) {
        if (!array_key_exists($group, $storedTags)) {
            throw new RuntimeException(
                'Tag group "' . $group . '" is missing.'
            );
        }
    }

    echo "\nDatabase test: OK\n";
} catch (\Throwable $e) {
    echo "\nDatabase test: FAILED\n";
    echo $e->getMessage() . "\n";

    exit(1);
} finally {
    echo "\nCleaning up test data...\n";

    cleanupTestData(
        $db,
        $source,
        $postId,
        $fileId
    );

    echo "Test data removed.\n";
}
