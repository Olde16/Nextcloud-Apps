<?php

declare(strict_types=1);

namespace OCA\E621Tags;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\BackgroundJob\TimedJob;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Server;
use Psr\Log\LoggerInterface;

class BulkScanJob extends TimedJob
{
    private const INTERVAL = 300; // 8 Min

    private const E621_PATH =
        '/Gemeinsame Daten/Furry/Artwork/Unsortiert/TWS_DB';

    private const E6AI_PATH =
        '/Gemeinsame Daten/Furry/Artwork/Unsortiert/TWS_AI_DB';

    private LoggerInterface $logger;
    private IRootFolder $rootFolder;
    private IUserManager $userManager;
    private IJobList $jobList;
    private FilenameParser $filenameParser;
    private FileTagService $fileTagService;
    private Queue $queue;
    private Config $config;

    public function __construct()
    {
        $time = Server::get(ITimeFactory::class);

        parent::__construct($time);

        $this->setInterval(self::INTERVAL);
        $this->setAllowParallelRuns(false);
        $this->setTimeSensitivity(TimedJob::TIME_INSENSITIVE);

        $this->logger = Server::get(LoggerInterface::class);
        $this->rootFolder = Server::get(IRootFolder::class);
        $this->userManager = Server::get(IUserManager::class);
        $this->jobList = Server::get(IJobList::class);
        $this->filenameParser = Server::get(FilenameParser::class);
        $this->fileTagService = Server::get(FileTagService::class);
        $this->queue = Server::get(Queue::class);
        $this->config = Server::get(Config::class);
    }

    protected function run($arguments): void
    {
        $queued = 0;
        $batchSize = $this->config->getBatchSize();

        $this->logger->info(
            'e621Tags: bulk scan started, batch size ' .
            $batchSize
        );

        $this->userManager->callForAllUsers(
            function (IUser $user) use (
                &$queued,
                $batchSize,
            ): bool {
                if ($queued >= $batchSize) {
                    return false;
                }

                $userFolder = $this->rootFolder->getUserFolder(
                    $user->getUID()
                );

                if ($this->config->isE621Enabled()) {
                    $queued += $this->scanFolder(
                        $userFolder,
                        self::E621_PATH,
                        false,
                        $queued,
                        $batchSize,
                    );
                }

                if ($queued >= $batchSize) {
                    return false;
                }

                if ($this->config->isE6aiEnabled()) {
                    $queued += $this->scanFolder(
                        $userFolder,
                        self::E6AI_PATH,
                        true,
                        $queued,
                        $batchSize,
                    );
                }

                return $queued < $batchSize;
            }
        );

        $this->logger->info(
            'e621Tags: bulk scan finished, queued ' .
            $queued .
            ' files'
        );
    }

    private function scanFolder(
        Folder $userFolder,
        string $path,
        bool $isE6ai,
        int $alreadyQueued,
        int $batchSize,
    ): int {
        if ($alreadyQueued >= $batchSize) {
            return 0;
        }

        try {
            $folder = $userFolder->get($path);

            if (!$folder instanceof Folder) {
                return 0;
            }

            $queued = 0;

            foreach ($folder->getDirectoryListing() as $node) {
                if ($alreadyQueued + $queued >= $batchSize) {
                    break;
                }

                if ($node instanceof Folder) {
                    $queued += $this->scanDirectory(
                        $node,
                        $isE6ai,
                        $alreadyQueued + $queued,
                        $batchSize,
                    );

                    continue;
                }

                if (!$node instanceof File) {
                    continue;
                }

                if ($this->queueFile($node, $isE6ai)) {
                    $queued++;
                }
            }

            return $queued;
        } catch (\Throwable $e) {
            $this->logger->warning(
                'e621Tags: bulk scan could not scan "' .
                $path .
                '": ' .
                $e->getMessage()
            );

            return 0;
        }
    }

    private function scanDirectory(
        Folder $folder,
        bool $isE6ai,
        int $alreadyQueued,
        int $batchSize,
    ): int {
        if ($alreadyQueued >= $batchSize) {
            return 0;
        }

        $queued = 0;

        foreach ($folder->getDirectoryListing() as $node) {
            if ($alreadyQueued + $queued >= $batchSize) {
                break;
            }

            if ($node instanceof Folder) {
                $queued += $this->scanDirectory(
                    $node,
                    $isE6ai,
                    $alreadyQueued + $queued,
                    $batchSize,
                );

                continue;
            }

            if (!$node instanceof File) {
                continue;
            }

            if ($this->queueFile($node, $isE6ai)) {
                $queued++;
            }
        }

        return $queued;
    }

    private function queueFile(
        File $file,
        bool $isE6ai,
    ): bool {
        if ($isE6ai) {
            if (!$this->config->isE6aiEnabled()) {
                return false;
            }
        } else {
            if (!$this->config->isE621Enabled()) {
                return false;
            }
        }

        $fileId = $file->getId();

        if ($this->fileTagService->hasProcessed($fileId)) {
            return false;
        }

        $postId = $this->filenameParser->extractPostId(
            $file->getName()
        );

        if ($postId === null) {
            return false;
        }

        $jobClass = $isE6ai
            ? E6aiTagJob::class
            : E621TagJob::class;

        if ($this->jobList->has(
            $jobClass,
            ['fileId' => $fileId]
        )) {
            return false;
        }

        if ($isE6ai) {
            $this->queue->addE6ai($fileId);

            $this->logger->info(
                'e621Tags: bulk scan queued e6AI file ' .
                $fileId .
                ' (' .
                $file->getName() .
                ')'
            );
        } else {
            $this->queue->addE621($fileId);

            $this->logger->info(
                'e621Tags: bulk scan queued e621 file ' .
                $fileId .
                ' (' .
                $file->getName() .
                ')'
            );
        }

        return true;
    }
}
