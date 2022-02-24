<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Tests\Services\ApplicationClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

abstract class AbstractSourceControllerTest extends WebTestCase
{
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

    protected function setUserServiceAuthorizedResponse(string $userId): void
    {
        $this->setUserServiceResponse(new Response(200, [], $userId));
    }

    protected function setUserServiceUnauthorizedResponse(): void
    {
        $this->setUserServiceResponse(new Response(401));
    }

    /**
     * @param array<string, int|string> $routeParameters
     */
    protected function generateUrl(string $routeName, array $routeParameters = []): string
    {
        return $this->router->generate($routeName, $routeParameters);
    }

    private function setUserServiceResponse(ResponseInterface $response): void
    {
        $mockHandler = self::getContainer()->get(MockHandler::class);
        \assert($mockHandler instanceof MockHandler);
        $mockHandler->append($response);
    }
}
