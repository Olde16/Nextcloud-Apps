<?php

declare(strict_types=1);

namespace OCP {
    interface IConfig
    {
    }
}

namespace OCP\AppFramework\Utility {
    interface ITimeFactory
    {
    }
}

namespace OCP\BackgroundJob {
    class QueuedJob
    {
        public function __construct(mixed $time = null)
        {
        }

        protected function setAllowParallelRuns(bool $allow): void
        {
        }
    }
}

namespace OCP\Files {
    class File
    {
    }

    interface IRootFolder
    {
    }
}

namespace Psr\Log {
    interface LoggerInterface
    {
    }
}

namespace OCA\E621Tags\Db {
    class ApiDataService
    {
    }
}

namespace OCA\E621Tags {
    class FileTagService
    {
    }

    require_once __DIR__ . '/../lib/Config.php';
    require_once __DIR__ . '/../lib/FileTagUpdateJob.php';

    class TestConfig extends Config
    {
        public bool $normalTagsEnabled = true;
        public bool $e621RatingEnabled = true;
        public bool $e6aiRatingEnabled = true;

        public bool $e621General = true;
        public bool $e621Artist = true;
        public bool $e621Character = true;
        public bool $e621Copyright = true;
        public bool $e621Species = true;
        public bool $e621Invalid = true;
        public bool $e621Lore = true;
        public bool $e621Meta = true;

        public bool $e6aiGeneral = true;
        public bool $e6aiDirector = true;
        public bool $e6aiCharacter = true;
        public bool $e6aiCopyright = true;
        public bool $e6aiSpecies = true;
        public bool $e6aiInvalid = true;
        public bool $e6aiLore = true;
        public bool $e6aiMeta = true;

        public function __construct()
        {
        }

        public function isNormalTagsEnabled(): bool
        {
            return $this->normalTagsEnabled;
        }

        public function isE621RatingEnabled(): bool
        {
            return $this->e621RatingEnabled;
        }

        public function isE6aiRatingEnabled(): bool
        {
            return $this->e6aiRatingEnabled;
        }

        public function isE621GeneralEnabled(): bool
        {
            return $this->e621General;
        }

        public function isE621ArtistEnabled(): bool
        {
            return $this->e621Artist;
        }

        public function isE621CharacterEnabled(): bool
        {
            return $this->e621Character;
        }

        public function isE621CopyrightEnabled(): bool
        {
            return $this->e621Copyright;
        }

        public function isE621SpeciesEnabled(): bool
        {
            return $this->e621Species;
        }

        public function isE621InvalidEnabled(): bool
        {
            return $this->e621Invalid;
        }

        public function isE621LoreEnabled(): bool
        {
            return $this->e621Lore;
        }

        public function isE621MetaEnabled(): bool
        {
            return $this->e621Meta;
        }

        public function isE6aiGeneralEnabled(): bool
        {
            return $this->e6aiGeneral;
        }

        public function isE6aiDirectorEnabled(): bool
        {
            return $this->e6aiDirector;
        }

        public function isE6aiCharacterEnabled(): bool
        {
            return $this->e6aiCharacter;
        }

        public function isE6aiCopyrightEnabled(): bool
        {
            return $this->e6aiCopyright;
        }

        public function isE6aiSpeciesEnabled(): bool
        {
            return $this->e6aiSpecies;
        }

        public function isE6aiInvalidEnabled(): bool
        {
            return $this->e6aiInvalid;
        }

        public function isE6aiLoreEnabled(): bool
        {
            return $this->e6aiLore;
        }

        public function isE6aiMetaEnabled(): bool
        {
            return $this->e6aiMeta;
        }
    }
}

namespace {
    use OCA\E621Tags\FileTagUpdateJob;
    use OCA\E621Tags\TestConfig;

    $config = new TestConfig();
    $job = (new ReflectionClass(FileTagUpdateJob::class))->newInstanceWithoutConstructor();

    $configProperty = new ReflectionProperty(FileTagUpdateJob::class, 'config');
    $configProperty->setValue($job, $config);

    $buildDesiredTags = new ReflectionMethod(
        FileTagUpdateJob::class,
        'buildDesiredTags'
    );
    $buildDesiredTags->setAccessible(true);

    $e621Tags = $buildDesiredTags->invoke(
        $job,
        'e621',
        [
            'general' => ['blue_eyes', 'tail'],
            'artist' => ['test_artist'],
            'meta' => ['test_meta'],
            'unknown' => ['must_not_be_included'],
        ],
        'q',
    );

    $expectedE621 = [
        'blue_eyes',
        'tail',
        'test_artist',
        'test_meta',
        'rating:questionable',
    ];

    if ($e621Tags !== $expectedE621) {
        echo "[FAIL] e621 desired tags\n";
        echo 'Expected: ' . var_export($expectedE621, true) . "\n";
        echo 'Actual:   ' . var_export($e621Tags, true) . "\n";
        exit(1);
    }

    echo "[PASS] e621 desired tags\n";

    $config->e621Meta = false;
    $config->e621RatingEnabled = false;

    $filteredE621Tags = $buildDesiredTags->invoke(
        $job,
        'e621',
        [
            'general' => ['blue_eyes'],
            'meta' => ['test_meta'],
        ],
        'q',
    );

    if ($filteredE621Tags !== ['blue_eyes']) {
        echo "[FAIL] e621 configuration filtering\n";
        echo 'Actual: ' . var_export($filteredE621Tags, true) . "\n";
        exit(1);
    }

    echo "[PASS] e621 configuration filtering\n";

    $config->e621Meta = true;
    $config->e621RatingEnabled = true;

    $e6aiTags = $buildDesiredTags->invoke(
        $job,
        'e6ai',
        [
            'general' => ['ai_general'],
            'director' => ['test_director'],
            'copyright' => ['test_copyright'],
            'franchise' => ['test_franchise'],
            'meta' => ['test_meta'],
            'unknown' => ['must_not_be_included'],
        ],
        'e',
    );

    $expectedE6ai = [
        'ai_general',
        'test_director',
        'test_copyright',
        'test_franchise',
        'test_meta',
        'rating:explicit',
    ];

    if ($e6aiTags !== $expectedE6ai) {
        echo "[FAIL] e6AI desired tags\n";
        echo 'Expected: ' . var_export($expectedE6ai, true) . "\n";
        echo 'Actual:   ' . var_export($e6aiTags, true) . "\n";
        exit(1);
    }

    echo "[PASS] e6AI desired tags\n";

    $config->normalTagsEnabled = false;

    $ratingOnly = $buildDesiredTags->invoke(
        $job,
        'e6ai',
        [
            'general' => ['must_not_be_included'],
        ],
        's',
    );

    if ($ratingOnly !== ['rating:safe']) {
        echo "[FAIL] Master normal-tags switch should leave rating independent\n";
        echo 'Actual: ' . var_export($ratingOnly, true) . "\n";
        exit(1);
    }

    echo "[PASS] Master normal-tags switch\n";
    echo "FileTagUpdateJob test: OK\n";
}
