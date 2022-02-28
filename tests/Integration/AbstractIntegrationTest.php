<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Tests\Services\ApplicationClient\Client;
use App\Tests\Services\AuthenticationConfiguration;
use App\Tests\Services\ResponseAsserter;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class AbstractIntegrationTest extends WebTestCase
{
    protected const AUTHENTICATED_USER_ID_PLACEHOLDER = '{{ authenticated_user_id }}';

    protected Client $client;
    protected ResponseAsserter $responseAsserter;
    protected AuthenticationConfiguration $authenticationConfiguration;
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
        $this->client = $application;

        $responseAsserter = self::getContainer()->get(ResponseAsserter::class);
        \assert($responseAsserter instanceof ResponseAsserter);
        $this->responseAsserter = $responseAsserter;
    }
}
