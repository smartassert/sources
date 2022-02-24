<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Tests\Services\ApplicationClient;
use App\Tests\Services\AuthenticationConfiguration;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

abstract class AbstractSourceControllerTest extends WebTestCase
{
    protected const AUTHENTICATED_USER_ID = '01FWKGED9E2NZZR5HP21HS2YYT';
    protected const AUTHENTICATED_USER_ID_PLACEHOLDER = '{{ authenticated_user_id }}';

    protected ApplicationClient $applicationClient;
    protected string $authenticatedUserId = '';
    private RouterInterface $router;

    protected function setUp(): void
    {
        parent::setUp();

        $client = static::createClient();

        $applicationClient = self::getContainer()->get(ApplicationClient::class);
        \assert($applicationClient instanceof ApplicationClient);
        $this->applicationClient = $applicationClient;
        $applicationClient->setClient($client);

        $router = self::getContainer()->get(RouterInterface::class);
        \assert($router instanceof RouterInterface);
        $this->router = $router;

        $authenticationConfiguration = self::getContainer()->get(AuthenticationConfiguration::class);
        \assert($authenticationConfiguration instanceof AuthenticationConfiguration);
        $this->authenticatedUserId = $authenticationConfiguration->authenticatedUserId;
    }

    /**
     * @param array<string, int|string> $routeParameters
     */
    protected function generateUrl(string $routeName, array $routeParameters = []): string
    {
        return $this->router->generate($routeName, $routeParameters);
    }
}
