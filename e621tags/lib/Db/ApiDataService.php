<?php

declare(strict_types=1);

namespace OCA\E621Tags\Db;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use RuntimeException;

class ApiDataService
{
    private const POSTS_TABLE = 'e621tags_posts';
    private const FILES_TABLE = 'e621tags_files';
    private const TAGS_TABLE = 'e621tags_tags';

    public function __construct(
        private IDBConnection $db,
    ) {
    }

    /**
     * Save or replace a complete API post and all of its tags.
     *
     * @param array<string, mixed> $post
     */
    public function savePost(
        string $source,
        int $fileId,
        int $postId,
        array $post,
    ): int {
        $this->validateSource($source);

        if ($fileId <= 0) {
            throw new InvalidArgumentException(
                'File ID must be greater than zero.'
            );
        }

        if ($postId <= 0) {
            throw new InvalidArgumentException(
                'Post ID must be greater than zero.'
            );
        }

        $tags = $post['tags'] ?? [];

        if (!is_array($tags)) {
            throw new RuntimeException(
                'API post contains invalid tag data.'
            );
        }

        $rawData = json_encode(
            $post,
            JSON_THROW_ON_ERROR |
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
        );

        $updatedAt = $this->getNullableString(
            $post['updated_at'] ?? null
        );

        $changeSeq = $this->getNullableInt(
            $post['change_seq'] ?? null
        );

        $rating = $this->getNullableString(
            $post['rating'] ?? null
        );

        $now = new DateTimeImmutable(
            'now',
            new DateTimeZone('UTC')
        );

        $this->db->beginTransaction();

        try {
            $postRecordId = $this->findPostRecordId(
                $source,
                $postId,
            );

            if ($postRecordId === null) {
                $this->insertPost(
                    $source,
                    $postId,
                    $updatedAt,
                    $changeSeq,
                    $rating,
                    $now,
                    $rawData,
                );

                $postRecordId = $this->findPostRecordId(
                    $source,
                    $postId,
                );

                if ($postRecordId === null) {
                    throw new RuntimeException(
                        'API post was inserted but could not be retrieved.'
                    );
                }
            } else {
                $this->updatePost(
                    $postRecordId,
                    $updatedAt,
                    $changeSeq,
                    $rating,
                    $now,
                    $rawData,
                );
            }

            $this->linkFile(
                $postRecordId,
                $fileId,
            );

            $this->replaceTags(
                $postRecordId,
                $tags,
            );

            $this->db->commit();

            return $postRecordId;
        } catch (\Throwable $e) {
            $this->db->rollBack();

            throw $e;
        }
    }

    /**
     * Refresh an existing API post without changing its file links.
     *
     * @param array<string, mixed> $post
     */
    public function refreshPost(
        string $source,
        int $postId,
        array $post,
    ): void {
        $this->validateSource($source);

        if ($postId <= 0) {
            throw new InvalidArgumentException(
                'Post ID must be greater than zero.'
            );
        }

        $tags = $post['tags'] ?? [];

        if (!is_array($tags)) {
            throw new RuntimeException(
                'API post contains invalid tag data.'
            );
        }

        $postRecordId = $this->findPostRecordId(
            $source,
            $postId,
        );

        if ($postRecordId === null) {
            throw new RuntimeException(
                'Cannot refresh unknown API post ' .
                $source .
                ':' .
                $postId .
                '.'
            );
        }

        $rawData = json_encode(
            $post,
            JSON_THROW_ON_ERROR |
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
        );

        $updatedAt = $this->getNullableString(
            $post['updated_at'] ?? null
        );

        $changeSeq = $this->getNullableInt(
            $post['change_seq'] ?? null
        );

        $rating = $this->getNullableString(
            $post['rating'] ?? null
        );

        $now = new DateTimeImmutable(
            'now',
            new DateTimeZone('UTC')
        );

        $this->db->beginTransaction();

        try {
            $this->updatePost(
                $postRecordId,
                $updatedAt,
                $changeSeq,
                $rating,
                $now,
                $rawData,
            );

            $this->replaceTags(
                $postRecordId,
                $tags,
            );

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();

            throw $e;
        }
    }

    /**
     * Mark a stored post as checked without changing its API data.
     */
    public function markChecked(
        int $postRecordId,
    ): void {
        if ($postRecordId <= 0) {
            throw new InvalidArgumentException(
                'Post record ID must be greater than zero.'
            );
        }

        $now = new DateTimeImmutable(
            'now',
            new DateTimeZone('UTC')
        );

        $qb = $this->db->getQueryBuilder();

        $qb->update(
            self::POSTS_TABLE
        )
            ->set(
                'checked_at',
                $qb->createNamedParameter(
                    $now->format('Y-m-d H:i:s'),
                    IQueryBuilder::PARAM_STR
                )
            )
            ->where(
                $qb->expr()->eq(
                    'id',
                    $qb->createNamedParameter(
                        $postRecordId,
                        IQueryBuilder::PARAM_INT
                    )
                )
            )
            ->executeStatement();
    }

    /**
     * Get stored posts that should be checked.
     *
     * @param list<string> $sources
     * @return list<array<string, mixed>>
     */
    public function getPostsForUpdate(
        array $sources,
        int $limit,
    ): array {
        if ($sources === []) {
            return [];
        }

        if ($limit <= 0) {
            return [];
        }

        foreach ($sources as $source) {
            $this->validateSource($source);
        }

        $qb = $this->db->getQueryBuilder();

        $sourceParameters = [];

        foreach ($sources as $source) {
            $sourceParameters[] = $qb->createNamedParameter(
                $source,
                IQueryBuilder::PARAM_STR
            );
        }

        $sourceExpression = $qb->expr()->in(
            'p.source',
            $sourceParameters
        );

        $qb->select(
            'p.id',
            'p.source',
            'p.post_id',
            'p.updated_at',
            'p.change_seq',
            'p.checked_at',
            'f.file_id',
        )
            ->from(
                self::POSTS_TABLE,
                'p'
            )
            ->innerJoin(
                'p',
                self::FILES_TABLE,
                'f',
                $qb->expr()->eq(
                    'f.post_record_id',
                    'p.id'
                )
            )
            ->where(
                $sourceExpression
            )
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->isNull('p.checked_at'),
                    $qb->expr()->isNotNull('p.checked_at')
                )
            )
            ->orderBy(
                'p.checked_at',
                'ASC'
            )
            ->addOrderBy(
                'p.id',
                'ASC'
            )
            ->setMaxResults($limit);

        $result = $qb->executeQuery();

        $rows = [];

        while ($row = $result->fetchAssociative()) {
            $rows[] = $row;
        }

        $result->closeCursor();

        /*
         * NULL values sort differently on different database engines.
         * Move never-checked records to the front explicitly.
         */
        usort(
            $rows,
            static function (
                array $a,
                array $b,
            ): int {
                $aChecked = $a['checked_at'];
                $bChecked = $b['checked_at'];

                if ($aChecked === null && $bChecked !== null) {
                    return -1;
                }

                if ($aChecked !== null && $bChecked === null) {
                    return 1;
                }

                return strcmp(
                    (string)$aChecked,
                    (string)$bChecked
                );
            }
        );

        return $rows;
    }

    /**
     * Find a stored post by its internal record ID.
     *
     * @return array<string, mixed>|null
     */
    public function findPostByRecordId(
        int $postRecordId,
    ): ?array {
        if ($postRecordId <= 0) {
            return null;
        }

        $qb = $this->db->getQueryBuilder();

        $qb->select(
            'p.*'
        )
            ->from(
                self::POSTS_TABLE,
                'p'
            )
            ->where(
                $qb->expr()->eq(
                    'p.id',
                    $qb->createNamedParameter(
                        $postRecordId,
                        IQueryBuilder::PARAM_INT
                    )
                )
            )
            ->setMaxResults(1);

        $result = $qb->executeQuery();
        $row = $result->fetchAssociative();
        $result->closeCursor();

        if ($row === false) {
            return null;
        }

        return $row;
    }

    /**
     * Find a stored post by API source and post ID.
     *
     * @return array<string, mixed>|null
     */
    public function findPostBySourceAndPostId(
        string $source,
        int $postId,
    ): ?array {
        $this->validateSource($source);

        $qb = $this->db->getQueryBuilder();

        $qb->select(
            'p.*'
        )
            ->from(
                self::POSTS_TABLE,
                'p'
            )
            ->where(
                $qb->expr()->eq(
                    'p.source',
                    $qb->createNamedParameter(
                        $source,
                        IQueryBuilder::PARAM_STR
                    )
                )
            )
            ->andWhere(
                $qb->expr()->eq(
                    'p.post_id',
                    $qb->createNamedParameter(
                        $postId,
                        IQueryBuilder::PARAM_INT
                    )
                )
            )
            ->setMaxResults(1);

        $result = $qb->executeQuery();
        $row = $result->fetchAssociative();
        $result->closeCursor();

        if ($row === false) {
            return null;
        }

        return $row;
    }

    /**
     * Find a stored post associated with a file.
     *
     * @return array<string, mixed>|null
     */
    public function findPostByFileId(
        int $fileId,
        string $source,
    ): ?array {
        $this->validateSource($source);

        $qb = $this->db->getQueryBuilder();

        $qb->select(
            'p.*'
        )
            ->from(
                self::POSTS_TABLE,
                'p'
            )
            ->innerJoin(
                'p',
                self::FILES_TABLE,
                'f',
                $qb->expr()->eq(
                    'f.post_record_id',
                    'p.id'
                )
            )
            ->where(
                $qb->expr()->eq(
                    'f.file_id',
                    $qb->createNamedParameter(
                        $fileId,
                        IQueryBuilder::PARAM_INT
                    )
                )
            )
            ->andWhere(
                $qb->expr()->eq(
                    'p.source',
                    $qb->createNamedParameter(
                        $source,
                        IQueryBuilder::PARAM_STR
                    )
                )
            )
            ->setMaxResults(1);

        $result = $qb->executeQuery();
        $row = $result->fetchAssociative();
        $result->closeCursor();

        if ($row === false) {
            return null;
        }

        return $row;
    }

    /**
     * Link a file to an existing stored API post.
     */
    public function linkFile(
        int $postRecordId,
        int $fileId,
    ): void {
        if ($postRecordId <= 0) {
            throw new InvalidArgumentException(
                'Post record ID must be greater than zero.'
            );
        }

        if ($fileId <= 0) {
            throw new InvalidArgumentException(
                'File ID must be greater than zero.'
            );
        }

        $qb = $this->db->getQueryBuilder();

        $qb->select('id')
            ->from(
                self::FILES_TABLE
            )
            ->where(
                $qb->expr()->eq(
                    'post_record_id',
                    $qb->createNamedParameter(
                        $postRecordId,
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
            ->setMaxResults(1);

        $result = $qb->executeQuery();
        $existing = $result->fetchOne();
        $result->closeCursor();

        if ($existing !== false) {
            return;
        }

        $insert = $this->db->getQueryBuilder();

        $insert->insert(
            self::FILES_TABLE
        )
            ->setValue(
                'post_record_id',
                $insert->createNamedParameter(
                    $postRecordId,
                    IQueryBuilder::PARAM_INT
                )
            )
            ->setValue(
                'file_id',
                $insert->createNamedParameter(
                    $fileId,
                    IQueryBuilder::PARAM_INT
                )
            )
            ->executeStatement();
    }

    /**
     * @return array<string, list<string>>
     */
    public function getTags(int $postRecordId): array
    {
        $qb = $this->db->getQueryBuilder();

        $qb->select(
            'group_name',
            'tag_name',
        )
            ->from(
                self::TAGS_TABLE
            )
            ->where(
                $qb->expr()->eq(
                    'post_record_id',
                    $qb->createNamedParameter(
                        $postRecordId,
                        IQueryBuilder::PARAM_INT
                    )
                )
            )
            ->orderBy(
                'id',
                'ASC'
            );

        $result = $qb->executeQuery();

        $groups = [];

        while ($row = $result->fetchAssociative()) {
            $group = (string)$row['group_name'];
            $tag = (string)$row['tag_name'];

            $groups[$group] ??= [];
            $groups[$group][] = $tag;
        }

        $result->closeCursor();

        return $groups;
    }

    private function insertPost(
        string $source,
        int $postId,
        ?string $updatedAt,
        ?int $changeSeq,
        ?string $rating,
        DateTimeImmutable $now,
        string $rawData,
    ): void {
        $qb = $this->db->getQueryBuilder();

        $qb->insert(
            self::POSTS_TABLE
        )
            ->setValue(
                'source',
                $qb->createNamedParameter(
                    $source,
                    IQueryBuilder::PARAM_STR
                )
            )
            ->setValue(
                'post_id',
                $qb->createNamedParameter(
                    $postId,
                    IQueryBuilder::PARAM_INT
                )
            )
            ->setValue(
                'updated_at',
                $qb->createNamedParameter(
                    $updatedAt,
                    IQueryBuilder::PARAM_STR
                )
            )
            ->setValue(
                'change_seq',
                $qb->createNamedParameter(
                    $changeSeq,
                    IQueryBuilder::PARAM_INT
                )
            )
            ->setValue(
                'rating',
                $qb->createNamedParameter(
                    $rating,
                    IQueryBuilder::PARAM_STR
                )
            )
            ->setValue(
                'fetched_at',
                $qb->createNamedParameter(
                    $now->format('Y-m-d H:i:s'),
                    IQueryBuilder::PARAM_STR
                )
            )
            ->setValue(
                'checked_at',
                $qb->createNamedParameter(
                    $now->format('Y-m-d H:i:s'),
                    IQueryBuilder::PARAM_STR
                )
            )
            ->setValue(
                'raw_data',
                $qb->createNamedParameter(
                    $rawData,
                    IQueryBuilder::PARAM_STR
                )
            )
            ->executeStatement();
    }

    private function updatePost(
        int $postRecordId,
        ?string $updatedAt,
        ?int $changeSeq,
        ?string $rating,
        DateTimeImmutable $now,
        string $rawData,
    ): void {
        $qb = $this->db->getQueryBuilder();

        $qb->update(
            self::POSTS_TABLE
        )
            ->set(
                'updated_at',
                $qb->createNamedParameter(
                    $updatedAt,
                    IQueryBuilder::PARAM_STR
                )
            )
            ->set(
                'change_seq',
                $qb->createNamedParameter(
                    $changeSeq,
                    IQueryBuilder::PARAM_INT
                )
            )
            ->set(
                'rating',
                $qb->createNamedParameter(
                    $rating,
                    IQueryBuilder::PARAM_STR
                )
            )
            ->set(
                'fetched_at',
                $qb->createNamedParameter(
                    $now->format('Y-m-d H:i:s'),
                    IQueryBuilder::PARAM_STR
                )
            )
            ->set(
                'checked_at',
                $qb->createNamedParameter(
                    $now->format('Y-m-d H:i:s'),
                    IQueryBuilder::PARAM_STR
                )
            )
            ->set(
                'raw_data',
                $qb->createNamedParameter(
                    $rawData,
                    IQueryBuilder::PARAM_STR
                )
            )
            ->where(
                $qb->expr()->eq(
                    'id',
                    $qb->createNamedParameter(
                        $postRecordId,
                        IQueryBuilder::PARAM_INT
                    )
                )
            )
            ->executeStatement();
    }

    /**
     * @param array<string, mixed> $tags
     */
    private function replaceTags(
        int $postRecordId,
        array $tags,
    ): void {
        $delete = $this->db->getQueryBuilder();

        $delete->delete(
            self::TAGS_TABLE
        )
            ->where(
                $delete->expr()->eq(
                    'post_record_id',
                    $delete->createNamedParameter(
                        $postRecordId,
                        IQueryBuilder::PARAM_INT
                    )
                )
            )
            ->executeStatement();

        foreach ($tags as $groupName => $groupTags) {
            if (!is_string($groupName) || !is_array($groupTags)) {
                continue;
            }

            foreach ($groupTags as $tagName) {
                if (!is_string($tagName)) {
                    continue;
                }

                $tagName = trim($tagName);

                if ($tagName === '') {
                    continue;
                }

                $insert = $this->db->getQueryBuilder();

                $insert->insert(
                    self::TAGS_TABLE
                )
                    ->setValue(
                        'post_record_id',
                        $insert->createNamedParameter(
                            $postRecordId,
                            IQueryBuilder::PARAM_INT
                        )
                    )
                    ->setValue(
                        'group_name',
                        $insert->createNamedParameter(
                            $groupName,
                            IQueryBuilder::PARAM_STR
                        )
                    )
                    ->setValue(
                        'tag_name',
                        $insert->createNamedParameter(
                            $tagName,
                            IQueryBuilder::PARAM_STR
                        )
                    )
                    ->executeStatement();
            }
        }
    }

    private function findPostRecordId(
        string $source,
        int $postId,
    ): ?int {
        $qb = $this->db->getQueryBuilder();

        $qb->select('id')
            ->from(
                self::POSTS_TABLE
            )
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
        $value = $result->fetchOne();
        $result->closeCursor();

        if ($value === false) {
            return null;
        }

        return (int)$value;
    }

    private function validateSource(string $source): void
    {
        if (!in_array($source, ['e621', 'e6ai'], true)) {
            throw new InvalidArgumentException(
                'Unsupported API source: ' . $source
            );
        }
    }

    private function getNullableString(
        mixed $value,
    ): ?string {
        if ($value === null) {
            return null;
        }

        if (!is_scalar($value)) {
            return null;
        }

        return (string)$value;
    }

    private function getNullableInt(
        mixed $value,
    ): ?int {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        return (int)$value;
    }
}
