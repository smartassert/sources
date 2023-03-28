<?php

declare(strict_types=1);

namespace App\Tests\Services\AuthenticationProvider;

use SmartAssert\UsersClient\Client;
use SmartAssert\UsersClient\Model\RefreshableToken;

class FrontendTokenProvider
{
    /**
     * @var RefreshableToken[]
     */
    private array $frontendTokens = [];

    /**
     * @param array{non-empty-string: non-empty-string} $userCredentials
     */
    public function __construct(
        private readonly array $userCredentials,
        private readonly Client $usersClient,
    ) {
    }

    public function get(string $userEmail): RefreshableToken
    {
        if (!array_key_exists($userEmail, $this->frontendTokens)) {
            $this->frontendTokens[$userEmail] = $this->usersClient->createFrontendToken(
                $userEmail,
                $this->userCredentials[$userEmail]
            );
        }

        return $this->frontendTokens[$userEmail];
    }
}
