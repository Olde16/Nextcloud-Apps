<?php

declare(strict_types=1);

namespace OCA\E621Tags;

use OCP\BackgroundJob\IJobList;

class Queue
{
    public function __construct(
        private IJobList $jobList,
        private Config $config,
    ) {
    }

    public function addE621(int $fileId): void
    {
        if (!$this->config->isE621Enabled()) {
            return;
        }

        $this->jobList->add(
            E621TagJob::class,
            [
                'fileId' => $fileId,
            ]
        );
    }

    public function addE6ai(int $fileId): void
    {
        if (!$this->config->isE6aiEnabled()) {
            return;
        }

        $this->jobList->add(
            E6aiTagJob::class,
            [
                'fileId' => $fileId,
            ]
        );
    }

    public function addFileTagUpdate(
        int $postRecordId,
        int $fileId,
    ): void {
        if ($postRecordId <= 0 || $fileId <= 0) {
            return;
        }

        $this->jobList->add(
            FileTagUpdateJob::class,
            [
                'postRecordId' => $postRecordId,
                'fileId' => $fileId,
            ]
        );
    }
}
