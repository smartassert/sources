<?php

declare(strict_types=1);

namespace App\Tests\Services\ApplicationClient;

use Psr\Http\Message\ResponseInterface;
use SmartAssert\SymfonyTestClient\ClientInterface;

class Client
{
    public function __construct(
        private ClientInterface $client,
    ) {}

    public function makeAddFileRequest(
        ?string $authenticationToken,
        string $sourceId,
        string $filename,
        string $content
    ): ResponseInterface {
        return $this->client->makeRequest(
            'POST',
            '/file-source/' . $sourceId . '/' . $filename,
            $this->createAuthorizationHeader($authenticationToken),
            $content
        );
    }

    public function makeReadFileRequest(
        ?string $authenticationToken,
        string $sourceId,
        string $filename
    ): ResponseInterface {
        return $this->client->makeRequest(
            'GET',
            '/file-source/' . $sourceId . '/' . $filename,
            $this->createAuthorizationHeader($authenticationToken),
        );
    }

    public function makeUpdateFileRequest(
        ?string $authenticationToken,
        string $sourceId,
        string $filename,
        string $content
    ): ResponseInterface {
        return $this->client->makeRequest(
            'PUT',
            '/file-source/' . $sourceId . '/' . $filename,
            $this->createAuthorizationHeader($authenticationToken),
            $content
        );
    }

    public function makeRemoveFileRequest(
        ?string $authenticationToken,
        string $sourceId,
        string $filename
    ): ResponseInterface {
        return $this->client->makeRequest(
            'DELETE',
            '/file-source/' . $sourceId . '/' . $filename,
            $this->createAuthorizationHeader($authenticationToken),
        );
    }

    /**
     * @param array<string, string> $payload
     */
    public function makeCreateFileSourceRequest(?string $authenticationToken, array $payload): ResponseInterface
    {
        $headers = array_merge(
            $this->createAuthorizationHeader($authenticationToken),
            [
                'content-type' => 'application/x-www-form-urlencoded',
            ]
        );

        return $this->client->makeRequest(
            'POST',
            '/file-source',
            $headers,
            http_build_query($payload)
        );
    }

    /**
     * @param array<string, string> $payload
     */
    public function makeCreateGitSourceRequest(?string $authenticationToken, array $payload): ResponseInterface
    {
        $headers = array_merge(
            $this->createAuthorizationHeader($authenticationToken),
            [
                'content-type' => 'application/x-www-form-urlencoded',
            ]
        );

        return $this->client->makeRequest(
            'POST',
            '/git-source',
            $headers,
            http_build_query($payload)
        );
    }

    public function makeListSourcesRequest(?string $authenticationToken): ResponseInterface
    {
        return $this->client->makeRequest(
            'GET',
            '/sources',
            $this->createAuthorizationHeader($authenticationToken),
        );
    }

    public function makeGetSourceRequest(?string $authenticationToken, string $sourceId): ResponseInterface
    {
        return $this->client->makeRequest(
            'GET',
            '/' . $sourceId,
            $this->createAuthorizationHeader($authenticationToken),
        );
    }

    /**
     * @param array<string, string> $payload
     */
    public function makeUpdateFileSourceRequest(
        ?string $authenticationToken,
        string $sourceId,
        array $payload
    ): ResponseInterface {
        $headers = array_merge(
            $this->createAuthorizationHeader($authenticationToken),
            [
                'content-type' => 'application/x-www-form-urlencoded',
            ]
        );

        return $this->client->makeRequest(
            'PUT',
            '/file-source/' . $sourceId,
            $headers,
            http_build_query($payload)
        );
    }

    /**
     * @param array<string, string> $payload
     */
    public function makeUpdateGitSourceRequest(
        ?string $authenticationToken,
        string $sourceId,
        array $payload
    ): ResponseInterface {
        $headers = array_merge(
            $this->createAuthorizationHeader($authenticationToken),
            [
                'content-type' => 'application/x-www-form-urlencoded',
            ]
        );

        return $this->client->makeRequest(
            'PUT',
            '/git-source/' . $sourceId,
            $headers,
            http_build_query($payload)
        );
    }

    public function makeDeleteSourceRequest(?string $authenticationToken, string $sourceId): ResponseInterface
    {
        return $this->client->makeRequest(
            'DELETE',
            '/' . $sourceId,
            $this->createAuthorizationHeader($authenticationToken),
        );
    }

    public function makeGetStatusRequest(): ResponseInterface
    {
        return $this->client->makeRequest('GET', '/status');
    }

    public function makeGetHealthCheckRequest(): ResponseInterface
    {
        return $this->client->makeRequest('GET', '/health-check');
    }

    public function makeGetFileSourceFilenamesRequest(?string $authenticationToken, string $sourceId): ResponseInterface
    {
        return $this->client->makeRequest(
            'GET',
            '/file-source/' . $sourceId . '/list/',
            $this->createAuthorizationHeader($authenticationToken),
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function makeCreateSuiteRequest(
        ?string $authenticationToken,
        array $payload
    ): ResponseInterface {
        $headers = array_merge(
            $this->createAuthorizationHeader($authenticationToken),
            [
                'content-type' => 'application/x-www-form-urlencoded',
            ]
        );

        return $this->client->makeRequest(
            'POST',
            '/suite',
            $headers,
            http_build_query($payload)
        );
    }

    public function makeGetSuiteRequest(?string $authenticationToken, string $suiteId): ResponseInterface
    {
        return $this->client->makeRequest(
            'GET',
            '/suite/' . $suiteId,
            $this->createAuthorizationHeader($authenticationToken),
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function makeUpdateSuiteRequest(
        ?string $authenticationToken,
        string $suiteId,
        array $payload,
    ): ResponseInterface {
        $headers = array_merge(
            $this->createAuthorizationHeader($authenticationToken),
            [
                'content-type' => 'application/x-www-form-urlencoded',
            ]
        );

        return $this->client->makeRequest(
            'PUT',
            '/suite/' . $suiteId,
            $headers,
            http_build_query($payload)
        );
    }

    public function makeListSuitesRequest(?string $authenticationToken): ResponseInterface
    {
        return $this->client->makeRequest(
            'GET',
            '/suites',
            $this->createAuthorizationHeader($authenticationToken),
        );
    }

    public function makeDeleteSuiteRequest(?string $authenticationToken, string $suiteId): ResponseInterface
    {
        return $this->client->makeRequest(
            'DELETE',
            '/suite/' . $suiteId,
            $this->createAuthorizationHeader($authenticationToken),
        );
    }

    public function makeReadSerializedSuiteRequest(?string $authenticationToken, string $suiteId): ResponseInterface
    {
        return $this->client->makeRequest(
            'GET',
            '/serialized_suite/' . $suiteId . '/read',
            $this->createAuthorizationHeader($authenticationToken),
        );
    }

    /**
     * @param array<string, string> $payload
     */
    public function makeCreateSerializedSuiteRequest(
        ?string $authenticationToken,
        string $serializedSuiteId,
        string $suiteId,
        array $payload
    ): ResponseInterface {
        $headers = array_merge(
            $this->createAuthorizationHeader($authenticationToken),
            [
                'content-type' => 'application/x-www-form-urlencoded',
            ]
        );

        return $this->client->makeRequest(
            'POST',
            '/suite/' . $suiteId . '/' . $serializedSuiteId,
            $headers,
            http_build_query($payload)
        );
    }

    public function makeGetSerializedSuiteRequest(
        ?string $authenticationToken,
        string $serializedSuiteId
    ): ResponseInterface {
        return $this->client->makeRequest(
            'GET',
            '/serialized_suite/' . $serializedSuiteId,
            $this->createAuthorizationHeader($authenticationToken),
        );
    }

    /**
     * @return array<string, string>
     */
    private function createAuthorizationHeader(?string $authenticationToken): array
    {
        $headers = [];
        if (is_string($authenticationToken)) {
            $headers = [
                'authorization' => 'Bearer ' . $authenticationToken,
            ];
        }

        return $headers;
    }
}
