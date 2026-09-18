<?php

declare(strict_types=1);

namespace OCA\E621Tags;

use OCP\BackgroundJob\IJobList;
use Psr\Log\LoggerInterface;

class Queue
{
    public function __construct(
        private IJobList $jobList,
        private Config $config,
        private LoggerInterface $logger,
    ) {
    }

    public function addE621(int $fileId): void
    {
        if (!$this->config->isE621Enabled()) {
            $this->logger->debug(
                'e621Tags: skipped E621 job for file ' . $fileId . ', API disabled'
            );

            return;
        }

        $this->jobList->add(
            E621TagJob::class,
            [
                'fileId' => $fileId,
            ]
        );

        $this->logger->info(
            'e621Tags: queued E621 job for file ' . $fileId
        );
    }

    public function addE6ai(int $fileId): void
    {
        if (!$this->config->isE6aiEnabled()) {
            $this->logger->debug(
                'e621Tags: skipped e6AI job for file ' . $fileId . ', API disabled'
            );

            return;
        }

        $this->jobList->add(
            E6aiTagJob::class,
            [
                'fileId' => $fileId,
            ]
        );

        $this->logger->info(
            'e621Tags: queued e6AI job for file ' . $fileId
        );
    }

    public function addFileTagUpdate(
        int $postRecordId,
        int $fileId,
    ): void {
        if ($postRecordId <= 0 || $fileId <= 0) {
            $this->logger->debug(
                'e621Tags: skipped file tag update job because of invalid arguments'
            );

            return;
        }

        $this->jobList->add(
            FileTagUpdateJob::class,
            [
                'postRecordId' => $postRecordId,
                'fileId' => $fileId,
            ]
        );

        $this->logger->info(
            'e621Tags: queued file tag update for post record ' .
            $postRecordId .
            ' and file ' .
            $fileId
        );
    }
}
