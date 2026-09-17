<?php

declare(strict_types=1);

namespace OCA\E621Tags\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IURLGenerator;

class AdminsettingsController extends Controller
{
    private const APP_ID = 'e621tags';

    public function __construct(
        IRequest $request,
        private IConfig $config,
        private IURLGenerator $urlGenerator,
    ) {
        parent::__construct(self::APP_ID, $request);
    }

    public function save(
        string $username,
        string $token,
        string $e6aiUsername,
        string $e6aiToken,
        bool $e621Enabled = true,
        bool $e6aiEnabled = true,
        bool $normalTagsEnabled = true,
        bool $e621General = true,
        bool $e621Artist = true,
        bool $e621Character = true,
        bool $e621Copyright = true,
        bool $e621Species = true,
        bool $e621Invalid = true,
        bool $e621Lore = true,
        bool $e621Meta = true,
        bool $e621Rating = true,
        bool $e6aiGeneral = true,
        bool $e6aiDirector = true,
        bool $e6aiCharacter = true,
        bool $e6aiCopyright = true,
        bool $e6aiSpecies = true,
        bool $e6aiInvalid = true,
        bool $e6aiLore = true,
        bool $e6aiMeta = true,
        bool $e6aiRating = true,
        int $batchSize = 30,
    ): RedirectResponse {
        $this->config->setAppValue(
            self::APP_ID,
            'e621_username',
            trim($username)
        );

        $this->config->setAppValue(
            self::APP_ID,
            'e621_token',
            trim($token)
        );

        $this->config->setAppValue(
            self::APP_ID,
            'e6ai_username',
            trim($e6aiUsername)
        );

        $this->config->setAppValue(
            self::APP_ID,
            'e6ai_token',
            trim($e6aiToken)
        );

        $settings = [
            'e621_enabled' => $e621Enabled,
            'e6ai_enabled' => $e6aiEnabled,
            'normal_tags_enabled' => $normalTagsEnabled,

            'e621_general' => $e621General,
            'e621_artist' => $e621Artist,
            'e621_character' => $e621Character,
            'e621_copyright' => $e621Copyright,
            'e621_species' => $e621Species,
            'e621_invalid' => $e621Invalid,
            'e621_lore' => $e621Lore,
            'e621_meta' => $e621Meta,
            'e621_rating' => $e621Rating,

            'e6ai_general' => $e6aiGeneral,
            'e6ai_director' => $e6aiDirector,
            'e6ai_character' => $e6aiCharacter,
            'e6ai_copyright' => $e6aiCopyright,
            'e6ai_species' => $e6aiSpecies,
            'e6ai_invalid' => $e6aiInvalid,
            'e6ai_lore' => $e6aiLore,
            'e6ai_meta' => $e6aiMeta,
            'e6ai_rating' => $e6aiRating,
        ];

        foreach ($settings as $key => $value) {
            $this->config->setAppValue(
                self::APP_ID,
                $key,
                $value ? '1' : '0'
            );
        }

        $batchSize = max(1, min(100, $batchSize));

        $this->config->setAppValue(
            self::APP_ID,
            'batch_size',
            (string)$batchSize
        );

        return new RedirectResponse(
            $this->urlGenerator->linkToRoute(
                'settings.AdminSettings.index',
                [
                    'section' => self::APP_ID,
                ]
            )
        );
    }
}
