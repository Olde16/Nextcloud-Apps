<?php

declare(strict_types=1);

namespace OCA\E621Tags\AppInfo;

use OCA\E621Tags\FileCreatedListener;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Files\Events\Node\NodeCreatedEvent;

class Application extends App implements IBootstrap
{
    public const APP_ID = 'e621tags';

    public function __construct(array $urlParams = [])
    {
        parent::__construct(self::APP_ID, $urlParams);
    }

    public function register(IRegistrationContext $context): void
    {
        $context->registerEventListener(
            NodeCreatedEvent::class,
            FileCreatedListener::class
        );
    }

    public function boot(IBootContext $context): void
    {
    }
}
