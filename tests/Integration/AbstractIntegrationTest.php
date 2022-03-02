<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Tests\Services\ApplicationClient\Client;
use App\Tests\Services\AuthenticationConfiguration;
use App\Tests\Services\ResponseAsserter;
use App\Tests\Services\SourceUserIdMutator;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class AbstractIntegrationTest extends WebTestCase
{
    protected Client $applicationClient;
    protected ResponseAsserter $responseAsserter;
    protected AuthenticationConfiguration $authenticationConfiguration;
    protected SourceUserIdMutator $sourceUserIdMutator;
    protected string $validToken;
    protected string $invalidToken;

    protected function setUp(): void
    {
        parent::setUp();

        static::createClient();

        $authenticationConfiguration = self::getContainer()->get(AuthenticationConfiguration::class);
        \assert($authenticationConfiguration instanceof AuthenticationConfiguration);
        $this->authenticationConfiguration = $authenticationConfiguration;

        $this->validToken = $authenticationConfiguration->validToken;
        $this->invalidToken = $authenticationConfiguration->invalidToken;

        $application = self::getContainer()->get('app.tests.services.application.client.integration');
        \assert($application instanceof Client);
        $this->applicationClient = $application;

        $responseAsserter = self::getContainer()->get(ResponseAsserter::class);
        \assert($responseAsserter instanceof ResponseAsserter);
        $this->responseAsserter = $responseAsserter;

        $sourceUserIdMutator = self::getContainer()->get(SourceUserIdMutator::class);
        \assert($sourceUserIdMutator instanceof SourceUserIdMutator);
        $this->sourceUserIdMutator = $sourceUserIdMutator;
    }
}
