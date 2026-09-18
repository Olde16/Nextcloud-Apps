<?php

declare(strict_types=1);

require_once '/var/www/nextcloud/lib/base.php';

use OCA\E621Tags\ApiRateLimiter;
use OCA\E621Tags\Config;
use OCA\E621Tags\E621Service;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use Psr\Log\LoggerInterface;

$config = \OC::$server->get(IConfig::class);
$clientService = \OC::$server->get(IClientService::class);
$rateLimiter = \OC::$server->get(ApiRateLimiter::class);
$logger = \OC::$server->get(LoggerInterface::class);

$service = new E621Service(
    $clientService,
    new Config($config),
    $rateLimiter,
    $logger,
);

$post = $service->getPost(1234567);

$parser = new \OCA\E621Tags\E621TagParser();

$tagGroups = $parser->parse($post);

var_dump($tagGroups);
