<?php

declare(strict_types=1);

namespace OCA\E621Tags;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\Settings\ISettings;

class AdminSettings implements ISettings
{
    private const APP_ID = 'e621tags';

    public function __construct(
        private IConfig $config,
        private IURLGenerator $urlGenerator,
    ) {
    }

    public function getForm(): TemplateResponse
    {
        return new TemplateResponse(
            self::APP_ID,
            'admin',
            [
                'username' => $this->config->getAppValue(
                    self::APP_ID,
                    'e621_username',
                    ''
                ),
                'token' => $this->config->getAppValue(
                    self::APP_ID,
                    'e621_token',
                    ''
                ),
                'e6ai_username' => $this->config->getAppValue(
                    self::APP_ID,
                    'e6ai_username',
                    ''
                ),
                'e6ai_token' => $this->config->getAppValue(
                    self::APP_ID,
                    'e6ai_token',
                    ''
                ),

                'e621_enabled' => $this->getBool(
                    'e621_enabled',
                    true
                ),
                'e6ai_enabled' => $this->getBool(
                    'e6ai_enabled',
                    true
                ),
                'normal_tags_enabled' => $this->getBool(
                    'normal_tags_enabled',
                    true
                ),

                'e621_general' => $this->getBool(
                    'e621_general',
                    true
                ),
                'e621_artist' => $this->getBool(
                    'e621_artist',
                    true
                ),
                'e621_character' => $this->getBool(
                    'e621_character',
                    true
                ),
                'e621_copyright' => $this->getBool(
                    'e621_copyright',
                    true
                ),
                'e621_species' => $this->getBool(
                    'e621_species',
                    true
                ),
                'e621_invalid' => $this->getBool(
                    'e621_invalid',
                    true
                ),
                'e621_lore' => $this->getBool(
                    'e621_lore',
                    true
                ),
                'e621_meta' => $this->getBool(
                    'e621_meta',
                    true
                ),
                'e621_rating' => $this->getBool(
                    'e621_rating',
                    true
                ),

                'e6ai_general' => $this->getBool(
                    'e6ai_general',
                    true
                ),
                'e6ai_director' => $this->getBool(
                    'e6ai_director',
                    true
                ),
                'e6ai_character' => $this->getBool(
                    'e6ai_character',
                    true
                ),
                'e6ai_copyright' => $this->getBool(
                    'e6ai_copyright',
                    true
                ),
                'e6ai_species' => $this->getBool(
                    'e6ai_species',
                    true
                ),
                'e6ai_invalid' => $this->getBool(
                    'e6ai_invalid',
                    true
                ),
                'e6ai_lore' => $this->getBool(
                    'e6ai_lore',
                    true
                ),
                'e6ai_meta' => $this->getBool(
                    'e6ai_meta',
                    true
                ),
                'e6ai_rating' => $this->getBool(
                    'e6ai_rating',
                    true
                ),

                'batch_size' => $this->config->getAppValue(
                    self::APP_ID,
                    'batch_size',
                    '30'
                ),

                'save_url' => $this->urlGenerator->linkToRoute(
                    'e621tags.adminsettings.save'
                ),
            ]
        );
    }

    public function getSection(): string
    {
        return self::APP_ID;
    }

    public function getPriority(): int
    {
        return 10;
    }

    private function getBool(
        string $key,
        bool $default,
    ): bool {
        return $this->config->getAppValue(
            self::APP_ID,
            $key,
            $default ? '1' : '0'
        ) === '1';
    }
}
