<?php

declare(strict_types=1);

namespace App\Tests\Services\AuthenticationProvider;

use SmartAssert\UsersClient\Model\User;

class Provider
{
    public function __construct(
        private readonly UserProvider $userProvider,
    ) {
    }

    public function getUser(string $userEmail): User
    {
        return $this->userProvider->get($userEmail);
    }
}
