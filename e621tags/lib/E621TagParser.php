<?php

declare(strict_types=1);

namespace OCA\E621Tags;

use RuntimeException;

class E621TagParser
{
    /**
     * @param array<string, mixed> $post
     * @return array<string, list<string>>
     */
    public function parse(array $post): array
    {
        if (!isset($post['tags']) || !is_array($post['tags'])) {
            throw new RuntimeException('Post does not contain tag data.');
        }

        $tags = $post['tags'];

        return [
            'general' => $this->getTagGroup($tags, 'general'),
            'artist' => $this->getTagGroup($tags, 'artist'),
            'character' => $this->getTagGroup($tags, 'character'),
            'copyright' => $this->getTagGroup($tags, 'copyright'),
            'species' => $this->getTagGroup($tags, 'species'),
            'invalid' => $this->getTagGroup($tags, 'invalid'),
            'lore' => $this->getTagGroup($tags, 'lore'),
            'meta' => $this->getTagGroup($tags, 'meta'),
        ];
    }

    public function parseRating(array $post): ?string
    {
        $rating = $post['rating'] ?? null;

        return match ($rating) {
            's' => 'rating:safe',
            'q' => 'rating:questionable',
            'e' => 'rating:explicit',
            default => null,
        };
    }

    /**
     * @param array<string, mixed> $tags
     * @return list<string>
     */
    private function getTagGroup(array $tags, string $group): array
    {
        if (!isset($tags[$group]) || !is_array($tags[$group])) {
            return [];
        }

        return array_values(
            array_filter(
                $tags[$group],
                'is_string'
            )
        );
    }
}
