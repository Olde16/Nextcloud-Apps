<?php

declare(strict_types=1);

namespace OCA\E621Tags;

class FilenameParser
{
    public function extractPostId(string $filename): ?int
    {
        $name = pathinfo($filename, PATHINFO_FILENAME);

        if (preg_match('/(?:^|[^0-9])([0-9]{1,12})(?=[^0-9]|$)/', $name, $matches)) {
            return (int)$matches[1];
        }

        return null;
    }
}
