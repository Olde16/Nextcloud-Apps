<?php

declare(strict_types=1);

define('OC_CONSOLE', true);

require '/var/www/nextcloud/lib/base.php';

use OCA\E621Tags\Db\ApiDataService;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Server;

$source = 'e621';
$postId = 999999992;
$fileId = 999999992;

$db = Server::get(IDBConnection::class);
$service = Server::get(ApiDataService::class);

function cleanupRefreshTestData(
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
                $qb->createNamedParameter($source, IQueryBuilder::PARAM_STR)
            )
        )
        ->andWhere(
            $qb->expr()->eq(
                'post_id',
                $qb->createNamedParameter($postId, IQueryBuilder::PARAM_INT)
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
                $qb->createNamedParameter($recordId, IQueryBuilder::PARAM_INT)
            )
        )
        ->executeStatement();

    $qb = $db->getQueryBuilder();
    $qb->delete('e621tags_files')
        ->where(
            $qb->expr()->eq(
                'post_record_id',
                $qb->createNamedParameter($recordId, IQueryBuilder::PARAM_INT)
            )
        )
        ->andWhere(
            $qb->expr()->eq(
                'file_id',
                $qb->createNamedParameter($fileId, IQueryBuilder::PARAM_INT)
            )
        )
        ->executeStatement();

    $qb = $db->getQueryBuilder();
    $qb->delete('e621tags_posts')
        ->where(
            $qb->expr()->eq(
                'id',
                $qb->createNamedParameter($recordId, IQueryBuilder::PARAM_INT)
            )
        )
        ->executeStatement();
}

$initialPost = [
    'updated_at' => '2026-09-17T08:00:00.000+00:00',
    'change_seq' => 100,
    'rating' => 's',
    'tags' => [
        'general' => ['old_tag'],
        'artist' => ['old_artist'],
    ],
];

$refreshedPost = [
    'updated_at' => '2026-09-17T09:00:00.000+00:00',
    'change_seq' => 101,
    'rating' => 'q',
    'tags' => [
        'general' => ['new_tag'],
        'artist' => ['new_artist'],
        'meta' => ['new_meta'],
    ],
];

try {
    cleanupRefreshTestData($db, $source, $postId, $fileId);

    $recordId = $service->savePost(
        $source,
        $fileId,
        $postId,
        $initialPost,
    );

    $stored = $service->findPostByRecordId($recordId);

    if ($stored === null || (int)$stored['post_id'] !== $postId) {
        throw new RuntimeException('Initial DB record could not be read back.');
    }

    if ($service->getTags($recordId) !== $initialPost['tags']) {
        throw new RuntimeException('Initial tags were not stored correctly.');
    }

    echo "[PASS] Initial post storage\n";

    $service->refreshPost(
        $source,
        $postId,
        $refreshedPost,
    );

    $refreshed = $service->findPostByRecordId($recordId);

    if ($refreshed === null) {
        throw new RuntimeException('Refreshed DB record could not be read back.');
    }

    if ((int)$refreshed['change_seq'] !== 101) {
        throw new RuntimeException('Refresh did not update change_seq.');
    }

    if ((string)$refreshed['rating'] !== 'q') {
        throw new RuntimeException('Refresh did not update rating.');
    }

    $storedTags = $service->getTags($recordId);

    if ($storedTags !== $refreshedPost['tags']) {
        echo '[FAIL] Refreshed tags' . "\n";
        echo 'Expected: ' . var_export($refreshedPost['tags'], true) . "\n";
        echo 'Actual:   ' . var_export($storedTags, true) . "\n";
        exit(1);
    }

    echo "[PASS] refreshPost replaces stored tags\n";

    $service->markChecked($recordId);
    $checked = $service->findPostByRecordId($recordId);

    if ($checked === null || $checked['checked_at'] === null) {
        throw new RuntimeException('markChecked did not set checked_at.');
    }

    echo "[PASS] markChecked\n";
    echo "Database refresh test: OK\n";
} catch (Throwable $e) {
    echo "[FAIL] Database refresh test\n";
    echo '       ' . $e->getMessage() . "\n";
    exit(1);
} finally {
    cleanupRefreshTestData($db, $source, $postId, $fileId);
}
