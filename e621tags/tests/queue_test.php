<?php

declare(strict_types=1);

namespace OCP {
    interface IConfig
    {
    }
}

namespace OCP\BackgroundJob {
    interface IJobList
    {
        public function add(string $jobClass, array $arguments = []): void;
    }
}

namespace OCA\E621Tags\Tests {
    require_once __DIR__ . '/../lib/Config.php';
    require_once __DIR__ . '/../lib/Queue.php';

    class TestConfig extends \OCA\E621Tags\Config
    {
        public bool $e621Enabled = true;
        public bool $e6aiEnabled = true;

        public function __construct()
        {
        }

        public function isE621Enabled(): bool
        {
            return $this->e621Enabled;
        }

        public function isE6aiEnabled(): bool
        {
            return $this->e6aiEnabled;
        }
    }

    class TestLogger implements \Psr\Log\LoggerInterface
    {
    }

    class FakeJobList implements \OCP\BackgroundJob\IJobList
    {
        /** @var list<array{jobClass: string, arguments: array<string, int>}> */
        public array $jobs = [];

        public function add(string $jobClass, array $arguments = []): void
        {
            $this->jobs[] = [
                'jobClass' => $jobClass,
                'arguments' => $arguments,
            ];
        }
    }
}

namespace {
    use OCA\E621Tags\E6aiTagJob;
    use OCA\E621Tags\E621TagJob;
    use OCA\E621Tags\FileTagUpdateJob;
    use OCA\E621Tags\Queue;
    use OCA\E621Tags\Tests\FakeJobList;
    use OCA\E621Tags\Tests\TestConfig;
    use OCA\E621Tags\Tests\TestLogger;

    $jobs = new FakeJobList();
    $config = new TestConfig();
    $queue = new Queue($jobs, $config, new TestLogger());

    $queue->addE621(101);

    if (
        count($jobs->jobs) !== 1 ||
        $jobs->jobs[0]['jobClass'] !== E621TagJob::class ||
        $jobs->jobs[0]['arguments'] !== ['fileId' => 101]
    ) {
        echo "[FAIL] Enabled e621 source should queue E621TagJob\n";
        exit(1);
    }

    echo "[PASS] Enabled e621 queueing\n";

    $config->e621Enabled = false;
    $queue->addE621(102);

    if (count($jobs->jobs) !== 1) {
        echo "[FAIL] Disabled e621 source should not queue a job\n";
        exit(1);
    }

    echo "[PASS] Disabled e621 queueing\n";

    $config->e6aiEnabled = true;
    $queue->addE6ai(201);

    if (
        count($jobs->jobs) !== 2 ||
        $jobs->jobs[1]['jobClass'] !== E6aiTagJob::class ||
        $jobs->jobs[1]['arguments'] !== ['fileId' => 201]
    ) {
        echo "[FAIL] Enabled e6AI source should queue E6aiTagJob\n";
        exit(1);
    }

    echo "[PASS] Enabled e6AI queueing\n";

    $config->e6aiEnabled = false;
    $queue->addE6ai(202);

    if (count($jobs->jobs) !== 2) {
        echo "[FAIL] Disabled e6AI source should not queue a job\n";
        exit(1);
    }

    echo "[PASS] Disabled e6AI queueing\n";

    $queue->addFileTagUpdate(303, 403);

    if (
        count($jobs->jobs) !== 3 ||
        $jobs->jobs[2]['jobClass'] !== FileTagUpdateJob::class ||
        $jobs->jobs[2]['arguments'] !== [
            'postRecordId' => 303,
            'fileId' => 403,
        ]
    ) {
        echo "[FAIL] Valid file tag update should be queued\n";
        exit(1);
    }

    echo "[PASS] File tag update queueing\n";

    $queue->addFileTagUpdate(0, 404);
    $queue->addFileTagUpdate(304, 0);
    $queue->addFileTagUpdate(-1, 405);
    $queue->addFileTagUpdate(305, -1);

    if (count($jobs->jobs) !== 3) {
        echo "[FAIL] Invalid file tag update arguments should not queue jobs\n";
        exit(1);
    }

    echo "[PASS] Invalid file tag update arguments\n";
    echo "Queue test: OK\n";
}
