<?php

declare(strict_types=1);

namespace App\Tests\Services\AuthenticationProvider;

use SmartAssert\UsersClient\Model\User;

class Provider
{
    public function __construct(
        private readonly UserProvider $userProvider,
        private readonly ApiTokenProvider $apiTokenProvider,
    ) {
    }

    public function getApiToken(string $userEmail): string
    {
        return $this->apiTokenProvider->get($userEmail);
    }

    public function getUser(string $userEmail): User
    {
        return $this->userProvider->get($userEmail);
    }
}
