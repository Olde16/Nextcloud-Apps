<?php

declare(strict_types=1);

namespace OCA\E621Tags;

use RuntimeException;

class ApiRateLimiter
{
    private const INITIAL_DELAY = 1.0;
    private const MAX_DELAY = 60.0;

    public function wait(string $api): void
    {
        $handle = $this->openStateFile($api);

        try {
            flock($handle, LOCK_EX);

            $state = $this->readState($handle);
            $now = microtime(true);

            $nextAllowed = (float)($state['next_allowed'] ?? 0);

            if ($nextAllowed > $now) {
                $sleepSeconds = $nextAllowed - $now;

                usleep((int)round($sleepSeconds * 1_000_000));

                $now = microtime(true);
            }

            $delay = $this->getDelay($state);

            $state['next_allowed'] = $now + $delay;

            $this->writeState($handle, $state);

            flock($handle, LOCK_UN);
        } finally {
            fclose($handle);
        }
    }

    public function success(string $api): void
    {
        $handle = $this->openStateFile($api);

        try {
            flock($handle, LOCK_EX);

            $state = $this->readState($handle);
            $delay = $this->getDelay($state);

            $delay = max(
                self::INITIAL_DELAY,
                $delay / 2
            );

            $state['delay'] = $delay;

            $this->writeState($handle, $state);

            flock($handle, LOCK_UN);
        } finally {
            fclose($handle);
        }
    }

    public function backoff(
        string $api,
        ?int $retryAfterSeconds = null,
    ): void {
        $handle = $this->openStateFile($api);

        try {
            flock($handle, LOCK_EX);

            $state = $this->readState($handle);
            $delay = $this->getDelay($state);

            $delay = min(
                self::MAX_DELAY,
                max(
                    $delay * 2,
                    (float)($retryAfterSeconds ?? 0),
                    self::INITIAL_DELAY
                )
            );

            $state['delay'] = $delay;
            $state['next_allowed'] = microtime(true) + $delay;

            $this->writeState($handle, $state);

            flock($handle, LOCK_UN);
        } finally {
            fclose($handle);
        }
    }

    private function openStateFile(string $api)
    {
        $safeApi = preg_replace(
            '/[^a-z0-9_-]/i',
            '_',
            $api
        );

        if ($safeApi === null || $safeApi === '') {
            throw new RuntimeException(
                'Invalid API rate limiter name.'
            );
        }

        $path = sys_get_temp_dir() .
            '/e621tags-ratelimit-' .
            $safeApi .
            '.json';

        $handle = fopen($path, 'c+');

        if ($handle === false) {
            throw new RuntimeException(
                'Could not open API rate limiter state file.'
            );
        }

        return $handle;
    }

    /**
     * @param resource $handle
     * @return array<string, mixed>
     */
    private function readState($handle): array
    {
        rewind($handle);

        $contents = stream_get_contents($handle);

        if ($contents === false || trim($contents) === '') {
            return [
                'delay' => self::INITIAL_DELAY,
                'next_allowed' => 0,
            ];
        }

        $state = json_decode(
            $contents,
            true
        );

        if (!is_array($state)) {
            return [
                'delay' => self::INITIAL_DELAY,
                'next_allowed' => 0,
            ];
        }

        return $state;
    }

    /**
     * @param array<string, mixed> $state
     */
    private function getDelay(array $state): float
    {
        $delay = (float)($state['delay'] ?? self::INITIAL_DELAY);

        return max(
            self::INITIAL_DELAY,
            min(self::MAX_DELAY, $delay)
        );
    }

    /**
     * @param resource $handle
     * @param array<string, mixed> $state
     */
    private function writeState($handle, array $state): void
    {
        rewind($handle);
        ftruncate($handle, 0);

        fwrite(
            $handle,
            json_encode(
                $state,
                JSON_THROW_ON_ERROR
            )
        );

        fflush($handle);
    }
}
