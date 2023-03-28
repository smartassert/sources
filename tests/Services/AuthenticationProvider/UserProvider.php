<?php

declare(strict_types=1);

namespace App\Tests\Services\AuthenticationProvider;

use SmartAssert\UsersClient\Client;
use SmartAssert\UsersClient\Model\User;

class UserProvider
{
    /**
     * @var User[]
     */
    private array $users = [];

    public function __construct(
        private readonly Client $usersClient,
        private readonly FrontendTokenProvider $frontendTokenProvider,
    ) {
    }

    public function get(string $userEmail): User
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
}
