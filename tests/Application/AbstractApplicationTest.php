<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Tests\Services\ApplicationClient\Client;
use App\Tests\Services\ApplicationClient\ClientFactory;
use App\Tests\Services\AuthenticationProvider\Provider;
use App\Tests\Services\EntityRemover;
use App\Tests\Services\ResponseAsserter;
use SmartAssert\SymfonyTestClient\ClientInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class AbstractApplicationTest extends WebTestCase
{
    protected const USER_1_EMAIL = 'user1@example.com';
    protected const USER_2_EMAIL = 'user2@example.com';

    protected static Provider $authenticationConfiguration;
    protected ResponseAsserter $responseAsserter;
    protected static KernelBrowser $kernelBrowser;
    protected Client $applicationClient;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$kernelBrowser = self::createClient();

        $authenticationConfiguration = self::getContainer()->get(Provider::class);
        \assert($authenticationConfiguration instanceof Provider);
        self::$authenticationConfiguration = $authenticationConfiguration;
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
