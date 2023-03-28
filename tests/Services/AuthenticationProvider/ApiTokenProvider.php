<?php

declare(strict_types=1);

namespace App\Tests\Services\AuthenticationProvider;

use SmartAssert\UsersClient\Client;
use SmartAssert\UsersClient\Model\ApiKey;
use SmartAssert\UsersClient\Model\Token;

class ApiTokenProvider
{
    /**
     * @var Token[]
     */
    private array $apiTokens = [];

    /**
     * @var ApiKey[]
     */
    private array $apiKeys = [];

    public function __construct(
        private readonly Client $usersClient,
        private readonly FrontendTokenProvider $frontendTokenProvider,
    ) {
    }

    /**
     * @param non-empty-string $userEmail
     *
     * @return non-empty-string
     */
    public function get(string $userEmail): string
    {
        if (!array_key_exists($userEmail, $this->apiTokens)) {
            $apiToken = $this->usersClient->createApiToken(
                $this->getApiKey($userEmail)->key
            );

            if (null === $apiToken) {
                throw new \RuntimeException('Valid API token is null');
            }

            $this->apiTokens[$userEmail] = $apiToken;
        }

        return $this->apiTokens[$userEmail]->token;
    }

    private function getApiKey(string $userEmail): ApiKey
    {
        if (!array_key_exists($userEmail, $this->apiKeys)) {
            $apiKeys = $this->usersClient->listUserApiKeys(
                $this->frontendTokenProvider->get($userEmail)
            );

            $apiKey = $apiKeys->getDefault();
            if (null === $apiKey) {
                throw new \RuntimeException('API key is null');
            }

            $this->apiKeys[$userEmail] = $apiKey;
        }

        return $this->apiKeys[$userEmail];
    }
}
