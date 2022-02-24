<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Tests\Services\ApplicationClient;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

abstract class AbstractSourceControllerTest extends WebTestCase
{
    protected const AUTHENTICATED_USER_ID = '01FWKGED9E2NZZR5HP21HS2YYT';

    protected ApplicationClient $applicationClient;
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
    }

    /**
     * @param array<string, int|string> $routeParameters
     */
    protected function generateUrl(string $routeName, array $routeParameters = []): string
    {
        return $this->router->generate($routeName, $routeParameters);
    }
}
