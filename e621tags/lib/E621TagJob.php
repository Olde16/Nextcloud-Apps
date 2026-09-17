<?php

declare(strict_types=1);

namespace OCA\E621Tags;

use OCA\E621Tags\Db\ApiDataService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use Psr\Log\LoggerInterface;

class E621TagJob extends QueuedJob
{
    public function __construct(
        ITimeFactory $time,
        private LoggerInterface $logger,
        private IRootFolder $rootFolder,
        private FileTagService $fileTagService,
        private FilenameParser $filenameParser,
        private E621Service $e621Service,
        private E621TagParser $tagParser,
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
            'e621Tags: START processing file ' . $fileId
        );

        if (!$this->config->isE621Enabled()) {
            $this->logger->info(
                'e621Tags: processing disabled'
            );

            return;
        }

        if ($fileId <= 0) {
            $this->logger->warning(
                'e621Tags: invalid file ID'
            );

            return;
        }

        try {
            $file = $this->findFile($fileId);

            if ($file === null) {
                $this->logger->warning(
                    'e621Tags: could not find file ' . $fileId
                );

                return;
            }

            $postId = $this->filenameParser->extractPostId(
                $file->getName()
            );

            if ($postId === null) {
                $this->logger->info(
                    'e621Tags: no e621 post ID found in "' .
                    $file->getName() .
                    '"'
                );

                return;
            }

            if ($this->fileTagService->hasProcessed($fileId)) {
                $this->logger->info(
                    'e621Tags: file ' .
                    $fileId .
                    ' is already processed'
                );

                return;
            }

            $storedPost = $this->apiDataService
                ->findPostBySourceAndPostId(
                    'e621',
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
                    'e621Tags: using stored API data for post ' .
                    $postId .
                    ' and file ' .
                    $fileId
                );
            } else {
                $this->logger->info(
                    'e621Tags: fetching e621 post ' .
                    $postId .
                    ' for file ' .
                    $fileId
                );

                $post = $this->e621Service->getPost($postId);

                $postRecordId = $this->apiDataService->savePost(
                    'e621',
                    $fileId,
                    $postId,
                    $post,
                );

                $tagGroups = $this->tagParser->parse($post);

                $rating = $post['rating'] ?? null;

                $this->logger->info(
                    'e621Tags: API data stored for post ' .
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

            if ($this->config->isE621RatingEnabled()) {
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
                'e621Tags: SUCCESS processing file ' .
                $fileId .
                ' from e621 post ' .
                $postId
            );
        } catch (\Throwable $e) {
            $this->logger->error(
                'e621Tags: failed to process file ' .
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
