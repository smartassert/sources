<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Tests\Services\ApplicationClient\Client;
use App\Tests\Services\ApplicationClient\ClientFactory;
use App\Tests\Services\EntityRemover;
use App\Tests\Services\ResponseAsserter;
use SmartAssert\SymfonyTestClient\ClientInterface;
use SmartAssert\TestAuthenticationProviderBundle\ApiTokenProvider;
use SmartAssert\TestAuthenticationProviderBundle\UserProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class AbstractApplicationTest extends WebTestCase
{
    protected const USER_1_EMAIL = 'user1@example.com';
    protected const USER_2_EMAIL = 'user2@example.com';

    protected ResponseAsserter $responseAsserter;
    protected static KernelBrowser $kernelBrowser;
    protected Client $applicationClient;
    protected static ApiTokenProvider $apiTokens;
    protected static UserProvider $users;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$kernelBrowser = self::createClient();

        $apiTokens = self::getContainer()->get(ApiTokenProvider::class);
        \assert($apiTokens instanceof ApiTokenProvider);
        self::$apiTokens = $apiTokens;

        $users = self::getContainer()->get(UserProvider::class);
        \assert($users instanceof UserProvider);
        self::$users = $users;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $factory = self::getContainer()->get(ClientFactory::class);
        \assert($factory instanceof ClientFactory);

        $this->applicationClient = $factory->create($this->getClientAdapter());

        $responseAsserter = self::getContainer()->get(ResponseAsserter::class);
        \assert($responseAsserter instanceof ResponseAsserter);
        $this->responseAsserter = $responseAsserter;

        $entityRemover = self::getContainer()->get(EntityRemover::class);
        if ($entityRemover instanceof EntityRemover) {
            $entityRemover->removeAll();
        }
    }

    abstract protected function getClientAdapter(): ClientInterface;
}
