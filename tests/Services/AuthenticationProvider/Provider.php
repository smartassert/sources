<?php

declare(strict_types=1);

namespace App\Tests\Services\AuthenticationProvider;

use SmartAssert\UsersClient\Client;
use SmartAssert\UsersClient\Model\Token;
use SmartAssert\UsersClient\Model\User;

class Provider
{
    /**
     * @var Token[]
     */
    private array $apiTokens = [];

    public function __construct(
        private readonly Client $usersClient,
        private readonly ApiKeyProvider $apiKeyProvider,
        private readonly UserProvider $userProvider,
    ) {
    }

    public function getValidApiToken(string $userEmail): string
    {
        if (!array_key_exists($userEmail, $this->apiTokens)) {
            $apiToken = $this->usersClient->createApiToken(
                $this->apiKeyProvider->get($userEmail)->key
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
        return $this->userProvider->get($userEmail);
    }
}
