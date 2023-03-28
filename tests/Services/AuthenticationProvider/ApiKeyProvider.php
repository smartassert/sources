<?php

declare(strict_types=1);

namespace App\Tests\Services\AuthenticationProvider;

use SmartAssert\UsersClient\Client;
use SmartAssert\UsersClient\Model\ApiKey;

class ApiKeyProvider
{
    /**
     * @var ApiKey[]
     */
    private array $apiKeys = [];

    public function __construct(
        private readonly Client $usersClient,
        private readonly FrontendTokenProvider $frontendTokenProvider,
    ) {
    }

    public function get(string $userEmail): ApiKey
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
