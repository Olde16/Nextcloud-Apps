<?php

declare(strict_types=1);

namespace OCA\E621Tags;

use OCA\E621Tags\Db\ApiDataService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use Psr\Log\LoggerInterface;

class FileTagUpdateJob extends QueuedJob
{
    public function __construct(
        ITimeFactory $time,
        private LoggerInterface $logger,
        private IRootFolder $rootFolder,
        private FileTagService $fileTagService,
        private ApiDataService $apiDataService,
        private Config $config,
    ) {
        parent::__construct($time);

        $this->setAllowParallelRuns(false);
    }

    protected function run($arguments): void
    {
        $postRecordId = (int)(
            $arguments['postRecordId'] ?? 0
        );

        $fileId = (int)(
            $arguments['fileId'] ?? 0
        );

        if ($postRecordId <= 0 || $fileId <= 0) {
            $this->logger->warning(
                'e621Tags: invalid arguments for file tag update'
            );

            return;
        }

        try {
            $post = $this->apiDataService->findPostByRecordId(
                $postRecordId
            );

            if ($post === null) {
                $this->logger->warning(
                    'e621Tags: stored post record ' .
                    $postRecordId .
                    ' could not be found'
                );

                return;
            }

            $source = (string)($post['source'] ?? '');

            if (!$this->isSourceEnabled($source)) {
                $this->logger->info(
                    'e621Tags: file tag update skipped for disabled source ' .
                    $source
                );

                return;
            }

            $postId = (int)($post['post_id'] ?? 0);

            $file = $this->findFile($fileId);

            if ($file === null) {
                $this->logger->warning(
                    'e621Tags: file ' .
                    $fileId .
                    ' no longer exists for ' .
                    $source .
                    ' post ' .
                    $postId
                );

                return;
            }

            $tagGroups = $this->apiDataService->getTags(
                $postRecordId
            );

            $rating = isset($post['rating'])
                ? (string)$post['rating']
                : null;

            $desiredTags = $this->buildDesiredTags(
                $source,
                $tagGroups,
                $rating,
            );

            $this->fileTagService->replaceTags(
                $fileId,
                $desiredTags,
            );

            $this->logger->info(
                'e621Tags: updated file tags for file ' .
                $fileId .
                ' from ' .
                $source .
                ' post ' .
                $postId
            );
        } catch (\Throwable $e) {
            $this->logger->error(
                'e621Tags: failed to update file tags for file ' .
                $fileId .
                ' and post record ' .
                $postRecordId .
                ': ' .
                $e->getMessage(),
                [
                    'exception' => $e,
                ]
            );

            throw $e;
        }
    }

    /**
     * @param array<string, list<string>> $tagGroups
     * @return list<string>
     */
    private function buildDesiredTags(
        string $source,
        array $tagGroups,
        ?string $rating,
    ): array {
        $allTags = [];

        if ($this->config->isNormalTagsEnabled()) {
            foreach ($tagGroups as $group => $tags) {
                if (!$this->isNormalGroupEnabled(
                    $source,
                    $group
                )) {
                    continue;
                }

                foreach ($tags as $tag) {
                    $allTags[] = $tag;
                }
            }
        }

        if (
            $source === 'e621' &&
            $this->config->isE621RatingEnabled()
        ) {
            $ratingTag = $this->parseStoredRating($rating);

            if ($ratingTag !== null) {
                $allTags[] = $ratingTag;
            }
        }

        if (
            $source === 'e6ai' &&
            $this->config->isE6aiRatingEnabled()
        ) {
            $ratingTag = $this->parseStoredRating($rating);

            if ($ratingTag !== null) {
                $allTags[] = $ratingTag;
            }
        }

        return array_values(
            array_unique($allTags)
        );
    }

    private function isSourceEnabled(
        string $source,
    ): bool {
        return match ($source) {
            'e621' => $this->config->isE621Enabled(),
            'e6ai' => $this->config->isE6aiEnabled(),
            default => false,
        };
    }

    private function isNormalGroupEnabled(
        string $source,
        string $group,
    ): bool {
        if ($source === 'e621') {
            return match ($group) {
                'general' => $this->config->isE621GeneralEnabled(),
                'artist' => $this->config->isE621ArtistEnabled(),
                'character' => $this->config->isE621CharacterEnabled(),
                'copyright' => $this->config->isE621CopyrightEnabled(),
                'species' => $this->config->isE621SpeciesEnabled(),
                'invalid' => $this->config->isE621InvalidEnabled(),
                'lore' => $this->config->isE621LoreEnabled(),
                'meta' => $this->config->isE621MetaEnabled(),
                default => false,
            };
        }

        if ($source === 'e6ai') {
            return match ($group) {
                'general' => $this->config->isE6aiGeneralEnabled(),
                'director' => $this->config->isE6aiDirectorEnabled(),
                'character' => $this->config->isE6aiCharacterEnabled(),
                'copyright' => $this->config->isE6aiCopyrightEnabled(),
                'franchise' => $this->config->isE6aiCopyrightEnabled(),
                'species' => $this->config->isE6aiSpeciesEnabled(),
                'invalid' => $this->config->isE6aiInvalidEnabled(),
                'lore' => $this->config->isE6aiLoreEnabled(),
                'meta' => $this->config->isE6aiMetaEnabled(),
                default => false,
            };
        }

        return false;
    }

    private function parseStoredRating(
        ?string $rating,
    ): ?string {
        return match ($rating) {
            's' => 'rating:safe',
            'q' => 'rating:questionable',
            'e' => 'rating:explicit',
            default => null,
        };
    }

    private function findFile(
        int $fileId,
    ): ?File {
        $files = $this->rootFolder->getById($fileId);

        foreach ($files as $file) {
            if ($file instanceof File) {
                return $file;
            }
        }

        return null;
    }
}
