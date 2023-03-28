<?php

declare(strict_types=1);

namespace App\Tests\Services\AuthenticationProvider;

use SmartAssert\UsersClient\Client;
use SmartAssert\UsersClient\Model\ApiKey;
use SmartAssert\UsersClient\Model\Token;
use SmartAssert\UsersClient\Model\User;

class Provider
{
    /**
     * @var ApiKey[]
     */
    private array $apiKeys = [];

    /**
     * @var Token[]
     */
    private array $apiTokens = [];

    /**
     * @var User[]
     */
    private array $users = [];

    public function __construct(
        private readonly Client $usersClient,
        private readonly FrontendTokenProvider $frontendTokenProvider,
    ) {
    }

    public function getValidApiToken(string $userEmail): string
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

    public function getInvalidApiToken(): string
    {
        return 'invalid api token value';
    }

    public function getUser(string $userEmail): User
    {
        if (!array_key_exists($userEmail, $this->users)) {
            $user = $this->usersClient->verifyFrontendToken(
                $this->frontendTokenProvider->get($userEmail)
            );

            if (null === $user) {
                throw new \RuntimeException('User is null');
            }

            $this->users[$userEmail] = $user;
        }

        return $this->users[$userEmail];
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
