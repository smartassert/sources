<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Tests\Services\ApplicationClient\Client;
use App\Tests\Services\ApplicationClient\SymfonyAdapter;
use App\Tests\Services\AuthenticationConfiguration;
use App\Tests\Services\ResponseAsserter;
use App\Tests\Services\SourceUserIdMutator;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class AbstractSourceControllerTest extends WebTestCase
{
    protected ResponseAsserter $responseAsserter;
    protected AuthenticationConfiguration $authenticationConfiguration;
    protected SourceUserIdMutator $sourceUserIdMutator;
    protected string $validToken;
    protected string $invalidToken;
    protected Client $applicationClient;

    protected function setUp(): void
    {
        parent::setUp();

        $client = static::createClient();

        $application = self::getContainer()->get('app.tests.services.application.client.functional');
        \assert($application instanceof Client);

        $symfonyClient = self::getContainer()->get(SymfonyAdapter::class);
        \assert($symfonyClient instanceof SymfonyAdapter);
        $symfonyClient->setKernelBrowser($client);

        $this->applicationClient = $application;

        $responseAsserter = self::getContainer()->get(ResponseAsserter::class);
        \assert($responseAsserter instanceof ResponseAsserter);
        $this->responseAsserter = $responseAsserter;

        $sourceUserIdMutator = self::getContainer()->get(SourceUserIdMutator::class);
        \assert($sourceUserIdMutator instanceof SourceUserIdMutator);
        $this->sourceUserIdMutator = $sourceUserIdMutator;

        $authenticationConfiguration = self::getContainer()->get(AuthenticationConfiguration::class);
        \assert($authenticationConfiguration instanceof AuthenticationConfiguration);
        $this->authenticationConfiguration = $authenticationConfiguration;

        $this->validToken = $authenticationConfiguration->validToken;
        $this->invalidToken = $authenticationConfiguration->invalidToken;
    }
}
