<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Tests\Model\Route;
use SmartAssert\UsersSecurityBundle\Security\AuthorizationProperties as AuthProperties;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class ApplicationClient
{
    public const AUTH_HEADER_KEY = AuthProperties::DEFAULT_HEADER_NAME;
    public const AUTH_HEADER_VALUE = AuthProperties::DEFAULT_VALUE_PREFIX . self::AUTH_TOKEN;
    public const AUTH_HEADER = [
        self::AUTH_HEADER_KEY => self::AUTH_HEADER_VALUE
    ];

    private const AUTH_TOKEN = 'authorization-token';
    private KernelBrowser $client;

    public function __construct(
        private RouterInterface $router,
    ) {
    }

    public function setClient(KernelBrowser $client): void
    {
        $this->client = $client;
    }

    /**
     * @param array<string, string> $parameters
     */
    public function makeAuthorizedSourceRequest(
        string $method,
        string $routeName,
        string $sourceId,
        array $parameters = []
    ): Response {
        return $this->makeAuthorizedRequest($method, new Route($routeName, ['sourceId' => $sourceId]), $parameters);
    }

    /**
     * @param array<string, string> $parameters
     */
    public function makeAuthorizedRequest(string $method, Route $route, array $parameters = []): Response
    {
        return $this->makeRequest(
            $method,
            $route,
            [AuthProperties::DEFAULT_HEADER_NAME => AuthProperties::DEFAULT_VALUE_PREFIX . self::AUTH_TOKEN],
            $parameters
        );
    }

    /**
     * @param array<string, string> $headers
     * @param array<string, string> $parameters
     */
    private function makeRequest(string $method, Route $route, array $headers, array $parameters): Response
    {
        $this->client->request(
            method: $method,
            uri: $this->router->generate($route->name, $route->parameters),
            parameters: $parameters,
            server: $this->createRequestServerPropertiesFromHeaders($headers)
        );

        return $this->client->getResponse();
    }

    /**
     * @param array<string, string> $headers
     *
     * @return array<string, string>
     */
    private function createRequestServerPropertiesFromHeaders(array $headers): array
    {
        $server = [];
        foreach ($headers as $key => $value) {
            $server['HTTP_' . $key] = $value;
        }

        return $server;
    }
}
