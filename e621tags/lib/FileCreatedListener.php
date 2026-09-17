<?php

declare(strict_types=1);

namespace OCA\E621Tags;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\File;
use OCP\Files\Events\Node\NodeCreatedEvent;
use Psr\Log\LoggerInterface;

/**
 * @template-implements IEventListener<NodeCreatedEvent>
 */
class FileCreatedListener implements IEventListener
{
    private const E621_PATH = '/Furry/Artwork/Unsortiert/TWS_DB/';
    private const E6AI_PATH = '/Furry/Artwork/Unsortiert/TWS_AI_DB/';

    public function __construct(
        private FilenameParser $filenameParser,
        private Queue $queue,
        private LoggerInterface $logger,
    ) {
    }

    public function handle(Event $event): void
    {
        if (!$event instanceof NodeCreatedEvent) {
            return;
        }

        $node = $event->getNode();

        if (!$node instanceof File) {
            return;
        }

        $postId = $this->filenameParser->extractPostId(
            $node->getName()
        );

        if ($postId === null) {
            return;
        }

        $path = $node->getPath();

        $this->logger->info(
            'e621Tags: detected post ID ' .
            $postId .
            ' in file "' .
            $node->getName() .
            '" with path "' .
            $path .
            '"'
        );

        if (str_contains($path, self::E621_PATH)) {
            $this->logger->info(
                'e621Tags: matched E621 path for file ' .
                $node->getId()
            );

            $this->queue->addE621($node->getId());

            return;
        }

        if (str_contains($path, self::E6AI_PATH)) {
            $this->logger->info(
                'e621Tags: matched E6AI path for file ' .
                $node->getId()
            );

            $this->queue->addE6ai($node->getId());

            return;
        }

        $this->logger->warning(
            'e621Tags: no source path matched for file ' .
            $node->getId()
        );
    }
}
