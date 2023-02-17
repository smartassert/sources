<?php

declare(strict_types=1);

namespace App\Tests\Services;

use SmartAssert\UsersClient\Client;
use SmartAssert\UsersClient\Model\ApiKey;
use SmartAssert\UsersClient\Model\RefreshableToken;
use SmartAssert\UsersClient\Model\Token;
use SmartAssert\UsersClient\Model\User;

class AuthenticationConfiguration
{
    /**
     * @var RefreshableToken[]
     */
    private array $frontendTokens = [];

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

    /**
     * @param array{non-empty-string: non-empty-string} $userCredentials
     */
    public function __construct(
        private readonly array $userCredentials,
        private readonly Client $usersClient,
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
                $this->getFrontendToken($userEmail)
            );

            if (null === $user) {
                throw new \RuntimeException('User is null');
            }

            $this->users[$userEmail] = $user;
        }

        return $this->users[$userEmail];
    }

    private function getFrontendToken(string $userEmail): RefreshableToken
    {
        if (!array_key_exists($userEmail, $this->frontendTokens)) {
            $this->frontendTokens[$userEmail] = $this->usersClient->createFrontendToken(
                $userEmail,
                $this->userCredentials[$userEmail]
            );
        }

        return $this->frontendTokens[$userEmail];
    }

    private function getApiKey(string $userEmail): ApiKey
    {
        if (!array_key_exists($userEmail, $this->apiKeys)) {
            $apiKeys = $this->usersClient->listUserApiKeys(
                $this->getFrontendToken($userEmail)
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
