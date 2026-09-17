<?php

declare(strict_types=1);

namespace OCA\E621Tags;

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
    ) {
        parent::__construct($time);

        $this->setAllowParallelRuns(false);
    }

    protected function run($arguments): void
    {
        $jobStart = microtime(true);

        $fileId = (int)($arguments['fileId'] ?? 0);

        $this->logger->warning(
            'e621Tags PROFILING: START file ' . $fileId
        );

        if ($fileId <= 0) {
            $this->logger->warning(
                'e621Tags: invalid file ID'
            );

            return;
        }

        try {
            $start = microtime(true);

            $file = $this->findFile($fileId);

            $this->logger->warning(
                'e621Tags PROFILING: findFile = ' .
                $this->formatDuration($start)
            );

            if ($file === null) {
                $this->logger->warning(
                    'e621Tags: could not find file ' . $fileId
                );

                return;
            }

            $start = microtime(true);

            $processed = $this->fileTagService->hasProcessed(
                $fileId
            );

            $this->logger->warning(
                'e621Tags PROFILING: hasProcessed = ' .
                $this->formatDuration($start)
            );

            if ($processed) {
                $this->logger->warning(
                    'e621Tags: file ' .
                    $fileId .
                    ' is already processed'
                );

                return;
            }

            $start = microtime(true);

            $postId = $this->filenameParser->extractPostId(
                $file->getName()
            );

            $this->logger->warning(
                'e621Tags PROFILING: filename parsing = ' .
                $this->formatDuration($start)
            );

            if ($postId === null) {
                $this->logger->warning(
                    'e621Tags: no e621 post ID found in "' .
                    $file->getName() .
                    '"'
                );

                return;
            }

            $start = microtime(true);

            $post = $this->e621Service->getPost($postId);

            $this->logger->warning(
                'e621Tags PROFILING: API request = ' .
                $this->formatDuration($start)
            );

            $start = microtime(true);

            $tagGroups = $this->tagParser->parse($post);

            $allTags = [];

            foreach ($tagGroups as $tags) {
                foreach ($tags as $tag) {
                    $allTags[] = $tag;
                }
            }

            $this->logger->warning(
                'e621Tags PROFILING: parsing/groups = ' .
                $this->formatDuration($start) .
                ' (' .
                count($allTags) .
                ' tags)'
            );

            $start = microtime(true);

            $this->fileTagService->addTags(
                $fileId,
                $allTags
            );

            $this->logger->warning(
                'e621Tags PROFILING: addTags = ' .
                $this->formatDuration($start)
            );

            $start = microtime(true);

            $this->fileTagService->markProcessed(
                $fileId
            );

            $this->logger->warning(
                'e621Tags PROFILING: markProcessed = ' .
                $this->formatDuration($start)
            );

            $this->logger->warning(
                'e621Tags PROFILING: TOTAL = ' .
                $this->formatDuration($jobStart)
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

    private function formatDuration(float $start): string
    {
        return number_format(
            microtime(true) - $start,
            3
        ) . 's';
    }
}
