<?php

declare(strict_types=1);

namespace OCA\E621Tags;

use OCP\SystemTag\ISystemTag;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use OCP\SystemTag\TagAlreadyExistsException;

class FileTagService
{
    private const OBJECT_TYPE = 'files';
    private const PROCESSED_TAG = 'Tagged by e621TagSystem';

    /**
     * @var array<string, ISystemTag>|null
     */
    private ?array $tagCache = null;

    public function __construct(
        private ISystemTagManager $tagManager,
        private ISystemTagObjectMapper $tagObjectMapper,
    ) {
    }

    public function hasProcessed(int $fileId): bool
    {
        $tag = $this->getOrCreateTag(
            self::PROCESSED_TAG,
            true,
            false,
        );

        return $this->tagObjectMapper->haveTag(
            (string)$fileId,
            self::OBJECT_TYPE,
            $tag->getId(),
        );
    }

    /**
     * @param list<string> $tags
     */
    public function addTags(int $fileId, array $tags): void
    {
        $tagIds = [];

        foreach ($tags as $tagName) {
            $tagName = trim($tagName);

            if ($tagName === '') {
                continue;
            }

            $tag = $this->getOrCreateTag($tagName);
            $tagIds[$tag->getId()] = true;
        }

        if ($tagIds === []) {
            return;
        }

        $this->tagObjectMapper->assignTags(
            (string)$fileId,
            self::OBJECT_TYPE,
            array_keys($tagIds),
        );
    }

    /**
     * Replace the current system tags with the supplied tags.
     *
     * The processed marker is preserved.
     *
     * @param list<string> $tags
     */
    public function replaceTags(
        int $fileId,
        array $tags,
    ): void {
        $desiredTagIds = [];

        foreach ($tags as $tagName) {
            $tagName = trim($tagName);

            if ($tagName === '') {
                continue;
            }

            $tag = $this->getOrCreateTag($tagName);

            $desiredTagIds[(string)$tag->getId()] = true;
        }

        $processedTag = $this->getOrCreateTag(
            self::PROCESSED_TAG,
            true,
            false,
        );

        $processedTagId = (string)$processedTag->getId();

        $assignedTagIdsByObject = $this->tagObjectMapper
            ->getTagIdsForObjects(
                [(string)$fileId],
                self::OBJECT_TYPE,
            );

        $assignedTagIds = $assignedTagIdsByObject[
            (string)$fileId
        ] ?? [];

        $removeTagIds = [];

        foreach ($assignedTagIds as $tagId) {
            $tagId = (string)$tagId;

            if ($tagId === $processedTagId) {
                continue;
            }

            if (isset($desiredTagIds[$tagId])) {
                continue;
            }

            $removeTagIds[] = $tagId;
        }

        if ($removeTagIds !== []) {
            $this->tagObjectMapper->unassignTags(
                (string)$fileId,
                self::OBJECT_TYPE,
                $removeTagIds,
            );
        }

        if ($desiredTagIds !== []) {
            $desiredIds = array_map(
                'strval',
                array_keys($desiredTagIds)
            );

            $this->tagObjectMapper->assignTags(
                (string)$fileId,
                self::OBJECT_TYPE,
                $desiredIds,
            );
        }

        $this->markProcessed($fileId);
    }

    public function markProcessed(int $fileId): void
    {
        $tag = $this->getOrCreateTag(
            self::PROCESSED_TAG,
            true,
            false,
        );

        $this->tagObjectMapper->assignTags(
            (string)$fileId,
            self::OBJECT_TYPE,
            $tag->getId(),
        );
    }

    private function getOrCreateTag(
        string $tagName,
        bool $userVisible = true,
        bool $userAssignable = true,
    ): ISystemTag {
        $this->loadTagCache();

        $cacheKey = mb_strtolower($tagName);

        if (isset($this->tagCache[$cacheKey])) {
            return $this->tagCache[$cacheKey];
        }

        try {
            $tag = $this->tagManager->createTag(
                $tagName,
                $userVisible,
                $userAssignable,
                null,
            );

            $this->tagCache[$cacheKey] = $tag;

            return $tag;
        } catch (TagAlreadyExistsException) {
            /*
             * Another process may have created the tag between
             * loading the cache and createTag().
             */
            $this->loadTagCache(true);

            if (isset($this->tagCache[$cacheKey])) {
                return $this->tagCache[$cacheKey];
            }

            throw new \RuntimeException(
                'Tag "' .
                $tagName .
                '" already exists but could not be retrieved.'
            );
        }
    }

    private function loadTagCache(bool $reload = false): void
    {
        if ($this->tagCache !== null && !$reload) {
            return;
        }

        $this->tagCache = [];

        $tags = $this->tagManager->getAllTags(
            null,
            null,
        );

        foreach ($tags as $tag) {
            $this->tagCache[
                mb_strtolower($tag->getName())
            ] = $tag;
        }
    }
}
