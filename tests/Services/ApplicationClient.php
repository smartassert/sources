<?php

declare(strict_types=1);

namespace App\Tests\Services;

use SmartAssert\UsersSecurityBundle\Security\AuthorizationProperties as AuthProperties;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;

class ApplicationClient
{
    public const AUTH_HEADER_KEY = AuthProperties::DEFAULT_HEADER_NAME;

    public const INVALID_AUTH_TOKEN = 'invalid-token';
    private KernelBrowser $client;

    public function __construct(
        private AuthenticationConfiguration $authenticationConfiguration,
    ) {
    }

    public function setClient(KernelBrowser $client): void
    {
        $this->client = $client;
    }

    /**
     * @param array<string, string> $parameters
     */
    public function makeUnauthorizedRequest(
        string $method,
        string $url,
        array $parameters = [],
        ?string $content = null
    ): Response {
        return $this->makeRequest(
            $method,
            $url,
            [
                AuthProperties::DEFAULT_HEADER_NAME => AuthProperties::DEFAULT_VALUE_PREFIX
                    . $this->authenticationConfiguration->invalid
            ],
            $parameters,
            $content,
        );
    }

    /**
     * @param array<string, string> $parameters
     */
    public function makeAuthorizedRequest(
        string $method,
        string $url,
        array $parameters = [],
        ?string $content = null
    ): Response {
        return $this->makeRequest(
            $method,
            $url,
            [
                AuthProperties::DEFAULT_HEADER_NAME => AuthProperties::DEFAULT_VALUE_PREFIX
                    . $this->authenticationConfiguration->valid
            ],
            $parameters,
            $content,
        );
    }

    /**
     * @param array<string, string> $headers
     * @param array<string, string> $parameters
     */
    private function makeRequest(
        string $method,
        string $url,
        array $headers,
        array $parameters,
        ?string $content = null
    ): Response {
        $this->client->request(
            method: $method,
            uri: $url,
            parameters: $parameters,
            server: $this->createRequestServerPropertiesFromHeaders($headers),
            content: $content,
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
