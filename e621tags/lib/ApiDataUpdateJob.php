<?php

declare(strict_types=1);

namespace OCA\E621Tags;

use OCA\E621Tags\Db\ApiDataService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use Psr\Log\LoggerInterface;

class ApiDataUpdateJob extends TimedJob
{
    private const INTERVAL = 86400; // 24 Stunden
    private const MAX_POSTS_PER_RUN = 100;

    public function __construct(
        ITimeFactory $time,
        private LoggerInterface $logger,
        private ApiDataService $apiDataService,
        private E621Service $e621Service,
        private E6aiService $e6aiService,
        private Config $config,
        private Queue $queue,
    ) {
        parent::__construct($time);

        $this->setInterval(self::INTERVAL);
        $this->setAllowParallelRuns(false);
        $this->setTimeSensitivity(
            TimedJob::TIME_INSENSITIVE
        );
    }

    protected function run($arguments): void
    {
        $sources = [];

        if ($this->config->isE621Enabled()) {
            $sources[] = 'e621';
        }

        if ($this->config->isE6aiEnabled()) {
            $sources[] = 'e6ai';
        }

        if ($sources === []) {
            $this->logger->info(
                'e621Tags: API data update skipped, all APIs disabled'
            );

            return;
        }

        $records = $this->apiDataService->getPostsForUpdate(
            $sources,
            self::MAX_POSTS_PER_RUN,
        );

        if ($records === []) {
            $this->logger->info(
                'e621Tags: API data update found no posts to check'
            );

            return;
        }

        $checked = 0;
        $updated = 0;
        $failed = 0;

        $this->logger->info(
            'e621Tags: API data update started, checking ' .
            count($records) .
            ' posts'
        );

        foreach ($records as $record) {
            $source = (string)$record['source'];
            $postId = (int)$record['post_id'];
            $recordId = (int)$record['id'];
            $fileId = (int)($record['file_id'] ?? 0);

            try {
                if ($fileId <= 0) {
                    throw new \RuntimeException(
                        'No valid file ID is linked to post record ' .
                        $recordId .
                        '.'
                    );
                }

                $post = $this->getPost(
                    $source,
                    $postId
                );

                $this->validatePostId(
                    $postId,
                    $post
                );

                if ($this->hasChanged($record, $post)) {
                    $this->apiDataService->refreshPost(
                        $source,
                        $postId,
                        $post,
                    );

                    $this->queue->addFileTagUpdate(
                        $recordId,
                        $fileId,
                    );

                    $updated++;

                    $this->logger->info(
                        'e621Tags: updated stored ' .
                        $source .
                        ' post ' .
                        $postId .
                        ' and queued file tag update for file ' .
                        $fileId
                    );
                } else {
                    $this->apiDataService->markChecked(
                        $recordId
                    );

                    $checked++;

                    $this->logger->info(
                        'e621Tags: checked unchanged ' .
                        $source .
                        ' post ' .
                        $postId
                    );
                }
            } catch (\Throwable $e) {
                $failed++;

                $this->logger->warning(
                    'e621Tags: failed to update ' .
                    $source .
                    ' post ' .
                    $postId .
                    ': ' .
                    $e->getMessage()
                );
            }
        }

        $this->logger->info(
            'e621Tags: API data update finished, checked ' .
            $checked .
            ', updated ' .
            $updated .
            ', failed ' .
            $failed
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function getPost(
        string $source,
        int $postId,
    ): array {
        return match ($source) {
            'e621' => $this->e621Service->getPost($postId),
            'e6ai' => $this->e6aiService->getPost($postId),
            default => throw new \RuntimeException(
                'Unsupported API source: ' . $source
            ),
        };
    }

    /**
     * @param array<string, mixed> $record
     * @param array<string, mixed> $post
     */
    private function hasChanged(
        array $record,
        array $post,
    ): bool {
        $storedChangeSeq = $this->getNullableInt(
            $record['change_seq'] ?? null
        );

        $newChangeSeq = $this->getNullableInt(
            $post['change_seq'] ?? null
        );

        if (
            $storedChangeSeq !== null &&
            $newChangeSeq !== null
        ) {
            return $storedChangeSeq !== $newChangeSeq;
        }

        $storedUpdatedAt = $this->getNullableString(
            $record['updated_at'] ?? null
        );

        $newUpdatedAt = $this->getNullableString(
            $post['updated_at'] ?? null
        );

        if (
            $storedUpdatedAt !== null &&
            $newUpdatedAt !== null
        ) {
            return $storedUpdatedAt !== $newUpdatedAt;
        }

        /*
         * If the API does not provide either comparison value,
         * we cannot prove that the stored data is still current.
         * Refreshing is therefore the safer choice.
         */
        return true;
    }

    /**
     * @param array<string, mixed> $post
     */
    private function validatePostId(
        int $postId,
        array $post,
    ): void {
        $apiPostId = $this->getNullableInt(
            $post['id'] ?? null
        );

        if (
            $apiPostId !== null &&
            $apiPostId !== $postId
        ) {
            throw new \RuntimeException(
                'API returned unexpected post ID ' .
                $apiPostId .
                ', expected ' .
                $postId .
                '.'
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
