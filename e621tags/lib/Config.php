<?php

declare(strict_types=1);

namespace OCA\E621Tags;

use OCP\IConfig;

class Config
{
    private const APP_ID = 'e621tags';

    public function __construct(
        private IConfig $config,
    ) {
    }

    public function getUsername(): string
    {
        return $this->config->getAppValue(
            self::APP_ID,
            'e621_username',
            ''
        );
    }

    public function getToken(): string
    {
        return $this->config->getAppValue(
            self::APP_ID,
            'e621_token',
            ''
        );
    }

    public function getE6aiUsername(): string
    {
        return $this->config->getAppValue(
            self::APP_ID,
            'e6ai_username',
            ''
        );
    }

    public function getE6aiToken(): string
    {
        return $this->config->getAppValue(
            self::APP_ID,
            'e6ai_token',
            ''
        );
    }

    public function isE621Enabled(): bool
    {
        return $this->config->getAppValue(
            self::APP_ID,
            'e621_enabled',
            '1'
        ) === '1';
    }

    public function isE6aiEnabled(): bool
    {
        return $this->config->getAppValue(
            self::APP_ID,
            'e6ai_enabled',
            '1'
        ) === '1';
    }

    public function isNormalTagsEnabled(): bool
    {
        return $this->config->getAppValue(
            self::APP_ID,
            'normal_tags_enabled',
            '1'
        ) === '1';
    }

    public function isE621GeneralEnabled(): bool
    {
        return $this->config->getAppValue(
            self::APP_ID,
            'e621_general',
            '1'
        ) === '1';
    }

    public function isE621ArtistEnabled(): bool
    {
        return $this->config->getAppValue(
            self::APP_ID,
            'e621_artist',
            '1'
        ) === '1';
    }

    public function isE621CharacterEnabled(): bool
    {
        return $this->config->getAppValue(
            self::APP_ID,
            'e621_character',
            '1'
        ) === '1';
    }

    public function isE621CopyrightEnabled(): bool
    {
        return $this->config->getAppValue(
            self::APP_ID,
            'e621_copyright',
            '1'
        ) === '1';
    }

    public function isE621SpeciesEnabled(): bool
    {
        return $this->config->getAppValue(
            self::APP_ID,
            'e621_species',
            '1'
        ) === '1';
    }

    public function isE621InvalidEnabled(): bool
    {
        return $this->config->getAppValue(
            self::APP_ID,
            'e621_invalid',
            '1'
        ) === '1';
    }

    public function isE621LoreEnabled(): bool
    {
        return $this->config->getAppValue(
            self::APP_ID,
            'e621_lore',
            '1'
        ) === '1';
    }

    public function isE621MetaEnabled(): bool
    {
        return $this->config->getAppValue(
            self::APP_ID,
            'e621_meta',
            '1'
        ) === '1';
    }

    public function isE621RatingEnabled(): bool
    {
        return $this->config->getAppValue(
            self::APP_ID,
            'e621_rating',
            '1'
        ) === '1';
    }

    public function isE6aiGeneralEnabled(): bool
    {
        return $this->config->getAppValue(
            self::APP_ID,
            'e6ai_general',
            '1'
        ) === '1';
    }

    public function isE6aiDirectorEnabled(): bool
    {
        return $this->config->getAppValue(
            self::APP_ID,
            'e6ai_director',
            '1'
        ) === '1';
    }

    public function isE6aiCharacterEnabled(): bool
    {
        return $this->config->getAppValue(
            self::APP_ID,
            'e6ai_character',
            '1'
        ) === '1';
    }

    public function isE6aiCopyrightEnabled(): bool
    {
        return $this->config->getAppValue(
            self::APP_ID,
            'e6ai_copyright',
            '1'
        ) === '1';
    }

    public function isE6aiSpeciesEnabled(): bool
    {
        return $this->config->getAppValue(
            self::APP_ID,
            'e6ai_species',
            '1'
        ) === '1';
    }

    public function isE6aiInvalidEnabled(): bool
    {
        return $this->config->getAppValue(
            self::APP_ID,
            'e6ai_invalid',
            '1'
        ) === '1';
    }

    public function isE6aiLoreEnabled(): bool
    {
        return $this->config->getAppValue(
            self::APP_ID,
            'e6ai_lore',
            '1'
        ) === '1';
    }

    public function isE6aiMetaEnabled(): bool
    {
        return $this->config->getAppValue(
            self::APP_ID,
            'e6ai_meta',
            '1'
        ) === '1';
    }

    public function isE6aiRatingEnabled(): bool
    {
        return $this->config->getAppValue(
            self::APP_ID,
            'e6ai_rating',
            '1'
        ) === '1';
    }

    public function getBatchSize(): int
    {
        $batchSize = (int)$this->config->getAppValue(
            self::APP_ID,
            'batch_size',
            '30'
        );

        return max(1, min(100, $batchSize));
    }
}
