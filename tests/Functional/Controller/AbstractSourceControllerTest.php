<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Tests\Services\ApplicationClient\Client;
use App\Tests\Services\ApplicationClient\SymfonyAdapter;
use App\Tests\Services\AuthenticationConfiguration;
use App\Tests\Services\RequestAsserter;
use App\Tests\Services\ResponseAsserter;
use App\Tests\Services\SourceUserIdMutator;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

abstract class AbstractSourceControllerTest extends WebTestCase
{
    protected const AUTHENTICATED_USER_ID_PLACEHOLDER = '{{ authenticated_user_id }}';

    protected RequestAsserter $requestAsserter;
    protected ResponseAsserter $responseAsserter;
    protected AuthenticationConfiguration $authenticationConfiguration;
    protected SourceUserIdMutator $sourceUserIdMutator;
    protected string $validToken;
    protected string $invalidToken;
    protected Client $application;
    private RouterInterface $router;

    protected function setUp(): void
    {
        parent::setUp();

        $client = static::createClient();

        $router = self::getContainer()->get(RouterInterface::class);
        \assert($router instanceof RouterInterface);
        $this->router = $router;

        $application = self::getContainer()->get('app.tests.services.application.client.functional');
        \assert($application instanceof Client);

        $symfonyClient = self::getContainer()->get(SymfonyAdapter::class);
        \assert($symfonyClient instanceof SymfonyAdapter);
        $symfonyClient->setKernelBrowser($client);

        $this->application = $application;

        $requestAsserter = self::getContainer()->get(RequestAsserter::class);
        \assert($requestAsserter instanceof RequestAsserter);
        $this->requestAsserter = $requestAsserter;

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

    /**
     * @param array<string, int|string> $routeParameters
     */
    protected function generateUrl(string $routeName, array $routeParameters = []): string
    {
        return $this->router->generate($routeName, $routeParameters);
    }
}
