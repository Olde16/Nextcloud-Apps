<?php

declare(strict_types=1);

namespace OCA\E621Tags;

use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

class AdminSection implements IIconSection
{
    public function __construct(
        private IL10N $l,
        private IURLGenerator $urlGenerator,
    ) {
    }

    public function getID(): string
    {
        return 'e621tags';
    }

    public function getName(): string
    {
        return $this->l->t('e621 Tags');
    }

    public function getPriority(): int
    {
        return 80;
    }

    public function getIcon(): string
    {
        return $this->urlGenerator->imagePath(
            'e621tags',
            'app.svg'
        );
    }
}
