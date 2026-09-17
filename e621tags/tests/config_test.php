<?php

declare(strict_types=1);

define('OC_CONSOLE', true);

require '/var/www/nextcloud/lib/base.php';

use OCA\E621Tags\Config;
use OCP\IConfig;
use OCP\Server;

$config = Server::get(IConfig::class);
$appId = 'e621tags';

$keys = [
    'e621_username',
    'e621_token',
    'e6ai_username',
    'e6ai_token',
    'e621_enabled',
    'e6ai_enabled',
    'normal_tags_enabled',
    'e621_general',
    'e621_artist',
    'e621_character',
    'e621_copyright',
    'e621_species',
    'e621_invalid',
    'e621_lore',
    'e621_meta',
    'e621_rating',
    'e6ai_general',
    'e6ai_director',
    'e6ai_character',
    'e6ai_copyright',
    'e6ai_species',
    'e6ai_invalid',
    'e6ai_lore',
    'e6ai_meta',
    'e6ai_rating',
    'batch_size',
];

$original = [];

foreach ($keys as $key) {
    $original[$key] = $config->getAppValue($appId, $key, null);
}

try {
    $values = [
        'e621_username' => 'test-e621-user',
        'e621_token' => 'test-e621-token',
        'e6ai_username' => 'test-e6ai-user',
        'e6ai_token' => 'test-e6ai-token',
        'e621_enabled' => '0',
        'e6ai_enabled' => '0',
        'normal_tags_enabled' => '0',
        'e621_general' => '0',
        'e621_artist' => '0',
        'e621_character' => '0',
        'e621_copyright' => '0',
        'e621_species' => '0',
        'e621_invalid' => '0',
        'e621_lore' => '0',
        'e621_meta' => '0',
        'e621_rating' => '0',
        'e6ai_general' => '0',
        'e6ai_director' => '0',
        'e6ai_character' => '0',
        'e6ai_copyright' => '0',
        'e6ai_species' => '0',
        'e6ai_invalid' => '0',
        'e6ai_lore' => '0',
        'e6ai_meta' => '0',
        'e6ai_rating' => '0',
        'batch_size' => '42',
    ];

    foreach ($values as $key => $value) {
        $config->setAppValue($appId, $key, $value);
    }

    $settings = new Config($config);

    $stringChecks = [
        'getUsername' => 'test-e621-user',
        'getToken' => 'test-e621-token',
        'getE6aiUsername' => 'test-e6ai-user',
        'getE6aiToken' => 'test-e6ai-token',
    ];

    foreach ($stringChecks as $method => $expected) {
        if ($settings->{$method}() !== $expected) {
            throw new RuntimeException(
                $method . ' returned an unexpected value.'
            );
        }
    }

    $booleanChecks = [
        'isE621Enabled',
        'isE6aiEnabled',
        'isNormalTagsEnabled',
        'isE621GeneralEnabled',
        'isE621ArtistEnabled',
        'isE621CharacterEnabled',
        'isE621CopyrightEnabled',
        'isE621SpeciesEnabled',
        'isE621InvalidEnabled',
        'isE621LoreEnabled',
        'isE621MetaEnabled',
        'isE621RatingEnabled',
        'isE6aiGeneralEnabled',
        'isE6aiDirectorEnabled',
        'isE6aiCharacterEnabled',
        'isE6aiCopyrightEnabled',
        'isE6aiSpeciesEnabled',
        'isE6aiInvalidEnabled',
        'isE6aiLoreEnabled',
        'isE6aiMetaEnabled',
        'isE6aiRatingEnabled',
    ];

    foreach ($booleanChecks as $method) {
        if ($settings->{$method}() !== false) {
            throw new RuntimeException(
                $method . ' should return false for a stored 0 value.'
            );
        }
    }

    if ($settings->getBatchSize() !== 42) {
        throw new RuntimeException('Batch size 42 was not returned correctly.');
    }

    echo "[PASS] Config getters\n";

    $config->setAppValue($appId, 'batch_size', '0');

    if ($settings->getBatchSize() !== 1) {
        throw new RuntimeException('Batch size must be clamped to a minimum of 1.');
    }

    echo "[PASS] Minimum batch size\n";

    $config->setAppValue($appId, 'batch_size', '150');

    if ($settings->getBatchSize() !== 100) {
        throw new RuntimeException('Batch size must be clamped to a maximum of 100.');
    }

    echo "[PASS] Maximum batch size\n";

    echo "Config test: OK\n";
} catch (Throwable $e) {
    echo "[FAIL] Config test\n";
    echo '       ' . $e->getMessage() . "\n";
    exit(1);
} finally {
    foreach ($original as $key => $value) {
        if ($value === null) {
            $config->deleteAppValue($appId, $key);
        } else {
            $config->setAppValue($appId, $key, $value);
        }
    }
}
