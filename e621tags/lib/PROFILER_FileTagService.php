<?php

declare(strict_types=1);

namespace OCA\E621Tags;

use OCP\SystemTag\ISystemTag;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use OCP\SystemTag\TagAlreadyExistsException;
use Psr\Log\LoggerInterface;

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
        private LoggerInterface $logger,
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
        $totalStart = microtime(true);

        $this->logger->warning(
            'e621Tags PROFILING: addTags entered for file ' .
            $fileId .
            ' with ' .
            count($tags) .
            ' tags'
        );

        $tagIds = [];

        $loadStart = microtime(true);

        $this->logger->warning(
            'e621Tags PROFILING: starting loadTagCache'
        );

        $this->loadTagCache();

        $this->logger->warning(
            'e621Tags PROFILING: loadTagCache = ' .
            $this->formatDuration($loadStart)
        );

        $createStart = microtime(true);

        foreach ($tags as $tagName) {
            $tagName = trim($tagName);

            if ($tagName === '') {
                continue;
            }

            $tagStart = microtime(true);

            $tag = $this->getOrCreateTag($tagName);

            $tagIds[$tag->getId()] = true;

            $this->logger->warning(
                'e621Tags PROFILING: tag "' .
                $tagName .
                '" = ' .
                $this->formatDuration($tagStart)
            );
        }

        $this->logger->warning(
            'e621Tags PROFILING: get/create tags = ' .
            $this->formatDuration($createStart) .
            ' (' .
            count($tagIds) .
            ' unique tags)'
        );

        if ($tagIds === []) {
            $this->logger->warning(
                'e621Tags PROFILING: addTags TOTAL = ' .
                $this->formatDuration($totalStart)
            );

            return;
        }

        $assignStart = microtime(true);

        $this->tagObjectMapper->assignTags(
            (string)$fileId,
            self::OBJECT_TYPE,
            array_keys($tagIds),
        );

        $this->logger->warning(
            'e621Tags PROFILING: assignTags = ' .
            $this->formatDuration($assignStart)
        );

        $this->logger->warning(
            'e621Tags PROFILING: addTags TOTAL = ' .
            $this->formatDuration($totalStart)
        );
    }

    public function markProcessed(int $fileId): void
    {
        $start = microtime(true);

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

        $this->logger->warning(
            'e621Tags PROFILING: markProcessed internal = ' .
            $this->formatDuration($start)
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

    private function formatDuration(float $start): string
    {
        return number_format(
            microtime(true) - $start,
            3
        ) . 's';
    }
}
