<?php

declare(strict_types=1);

namespace OCA\E621Tags;

use OCP\Http\Client\IClientService;
use RuntimeException;

class E621Service
{
    private const BASE_URL = 'https://e621.net';
    private const USER_AGENT = 'Nextcloud-e621Tags/0.1.0';

    public function __construct(
        private IClientService $clientService,
        private Config $config,
        private ApiRateLimiter $rateLimiter,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getPost(int $postId): array
    {
        if ($postId <= 0) {
            throw new RuntimeException(
                'Invalid e621 post ID.'
            );
        }

        $username = $this->config->getUsername();
        $token = $this->config->getToken();

        if ($username === '' || $token === '') {
            throw new RuntimeException(
                'e621 username and API token are not configured.'
            );
        }

        $client = $this->clientService->newClient();

        $maxAttempts = 4;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $this->rateLimiter->wait('e621');

            $response = $client->get(
                self::BASE_URL . '/posts/' . $postId . '.json',
                [
                    'auth' => [
                        $username,
                        $token,
                    ],
                    'headers' => [
                        'Accept' => 'application/json',
                        'User-Agent' => self::USER_AGENT,
                    ],
                    'connect_timeout' => 5,
                    'timeout' => 15,
                ]
            );

            $statusCode = $response->getStatusCode();

            if (
                $statusCode === 421 ||
                $statusCode === 429 ||
                $statusCode === 503
            ) {
                $retryAfter = $this->getRetryAfterSeconds(
                    $response->getHeader('Retry-After')
                );

                $this->rateLimiter->backoff(
                    'e621',
                    $retryAfter
                );

                if ($attempt < $maxAttempts) {
                    continue;
                }
            }

            if ($statusCode !== 200) {
                throw new RuntimeException(
                    'e621 returned HTTP ' .
                    $statusCode .
                    '.'
                );
            }

            $this->rateLimiter->success('e621');

            $data = json_decode(
                $response->getBody(),
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            if (
                !isset($data['post']) ||
                !is_array($data['post'])
            ) {
                throw new RuntimeException(
                    'e621 returned an unexpected response.'
                );
            }

            return $data['post'];
        }

        throw new RuntimeException(
            'e621 request failed after retries.'
        );
    }

    private function getRetryAfterSeconds(
        string $header,
    ): ?int {
        $header = trim($header);

        if ($header === '') {
            return null;
        }

        if (ctype_digit($header)) {
            return (int)$header;
        }

        $timestamp = strtotime($header);

        if ($timestamp === false) {
            return null;
        }

        return max(
            0,
            $timestamp - time()
        );
    }
}
