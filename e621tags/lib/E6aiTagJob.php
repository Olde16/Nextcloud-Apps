<?php

declare(strict_types=1);

namespace OCA\E621Tags;

use OCA\E621Tags\Db\ApiDataService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use Psr\Log\LoggerInterface;

class E6aiTagJob extends QueuedJob
{
    public function __construct(
        ITimeFactory $time,
        private LoggerInterface $logger,
        private IRootFolder $rootFolder,
        private FileTagService $fileTagService,
        private FilenameParser $filenameParser,
        private E6aiService $e6aiService,
        private E6aiTagParser $tagParser,
        private Config $config,
        private ApiDataService $apiDataService,
    ) {
        parent::__construct($time);

        $this->setAllowParallelRuns(false);
    }

    protected function run($arguments): void
    {
        $fileId = (int)($arguments['fileId'] ?? 0);

        $this->logger->info(
            'e6AI: START processing file ' . $fileId
        );

        if (!$this->config->isE6aiEnabled()) {
            $this->logger->info(
                'e6AI: processing disabled'
            );

            return;
        }

        if ($fileId <= 0) {
            $this->logger->warning(
                'e6AI: invalid file ID'
            );

            return;
        }

        try {
            $file = $this->findFile($fileId);

            if ($file === null) {
                $this->logger->warning(
                    'e6AI: could not find file ' . $fileId
                );

                return;
            }

            $postId = $this->filenameParser->extractPostId(
                $file->getName()
            );

            if ($postId === null) {
                $this->logger->info(
                    'e6AI: no post ID found in "' .
                    $file->getName() .
                    '"'
                );

                return;
            }

            if ($this->fileTagService->hasProcessed($fileId)) {
                $this->logger->info(
                    'e6AI: file ' .
                    $fileId .
                    ' is already processed'
                );

                return;
            }

            $storedPost = $this->apiDataService
                ->findPostBySourceAndPostId(
                    'e6ai',
                    $postId,
                );

            if ($storedPost !== null) {
                $postRecordId = (int)$storedPost['id'];

                $this->apiDataService->linkFile(
                    $postRecordId,
                    $fileId,
                );

                $tagGroups = $this->apiDataService->getTags(
                    $postRecordId
                );

                $rating = isset($storedPost['rating'])
                    ? (string)$storedPost['rating']
                    : null;

                $this->logger->info(
                    'e6AI: using stored API data for post ' .
                    $postId .
                    ' and file ' .
                    $fileId
                );
            } else {
                $this->logger->info(
                    'e6AI: fetching post ' .
                    $postId .
                    ' for file ' .
                    $fileId
                );

                $post = $this->e6aiService->getPost($postId);

                $postRecordId = $this->apiDataService->savePost(
                    'e6ai',
                    $fileId,
                    $postId,
                    $post,
                );

                $tagGroups = $this->tagParser->parse($post);

                $rating = $post['rating'] ?? null;

                $this->logger->info(
                    'e6AI: API data stored for post ' .
                    $postId
                );
            }

            $allTags = [];

            if ($this->config->isNormalTagsEnabled()) {
                foreach ($tagGroups as $group => $tags) {
                    if (!$this->isNormalGroupEnabled($group)) {
                        continue;
                    }

                    foreach ($tags as $tag) {
                        $allTags[] = $tag;
                    }
                }
            }

            if ($this->config->isE6aiRatingEnabled()) {
                $ratingTag = $this->parseStoredRating($rating);

                if ($ratingTag !== null) {
                    $allTags[] = $ratingTag;
                }
            }

            $this->fileTagService->addTags(
                $fileId,
                $allTags
            );

            $this->fileTagService->markProcessed($fileId);

            $this->logger->info(
                'e6AI: SUCCESS processing file ' .
                $fileId .
                ' from e6AI post ' .
                $postId
            );
        } catch (\Throwable $e) {
            $this->logger->error(
                'e6AI: failed to process file ' .
                $fileId .
                ': ' .
                $e->getMessage(),
                [
                    'exception' => $e,
                ]
            );

            throw $e;
        }
    }

    private function isNormalGroupEnabled(
        string $group,
    ): bool {
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

    private function findFile(int $fileId): ?File
    {
        $files = $this->rootFolder->getById($fileId);

        foreach ($files as $file) {
            if ($file instanceof File) {
                return $file;
            }
        }

        return null;
    }
}
