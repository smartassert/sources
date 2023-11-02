<?php

declare(strict_types=1);

namespace App\Tests\Services\ApplicationClient;

use Psr\Http\Message\ResponseInterface;
use SmartAssert\SymfonyTestClient\ClientInterface;
use Symfony\Component\Routing\RouterInterface;

class Client
{
    public function __construct(
        private ClientInterface $client,
        private RouterInterface $router,
    ) {
    }

    public function makeAddFileRequest(
        ?string $authenticationToken,
        string $sourceId,
        string $filename,
        string $content
    ): ResponseInterface {
        return $this->client->makeRequest(
            'POST',
            $this->router->generate('file_source_file_add', ['sourceId' => $sourceId, 'filename' => $filename]),
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
            $this->router->generate('file_source_file_read', ['sourceId' => $sourceId, 'filename' => $filename]),
            $this->createAuthorizationHeader($authenticationToken),
        );
    }

    public function makeRemoveFileRequest(
        ?string $authenticationToken,
        string $sourceId,
        string $filename
    ): ResponseInterface {
        return $this->client->makeRequest(
            'DELETE',
            $this->router->generate('file_source_file_remove', ['sourceId' => $sourceId, 'filename' => $filename]),
            $this->createAuthorizationHeader($authenticationToken),
        );
    }

    /**
     * @param array<string, string> $payload
     */
    public function makeCreateSourceRequest(?string $authenticationToken, array $payload): ResponseInterface
    {
        $headers = array_merge(
            $this->createAuthorizationHeader($authenticationToken),
            [
                'content-type' => 'application/x-www-form-urlencoded',
            ]
        );

        return $this->client->makeRequest(
            'POST',
            $this->router->generate('source_create'),
            $headers,
            http_build_query($payload)
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
            $this->router->generate('file_source_create'),
            $headers,
            http_build_query($payload)
        );
    }

    public function makeListSourcesRequest(?string $authenticationToken): ResponseInterface
    {
        return $this->client->makeRequest(
            'GET',
            $this->router->generate('source_list'),
            $this->createAuthorizationHeader($authenticationToken),
        );
    }

    public function makeGetSourceRequest(?string $authenticationToken, string $sourceId): ResponseInterface
    {
        return $this->client->makeRequest(
            'GET',
            $this->router->generate('user_source_get', ['sourceId' => $sourceId]),
            $this->createAuthorizationHeader($authenticationToken),
        );
    }

    /**
     * @param array<string, string> $payload
     */
    public function makeUpdateSourceRequest(
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
            $this->router->generate('user_source_update', ['sourceId' => $sourceId]),
            $headers,
            http_build_query($payload)
        );
    }

    public function makeDeleteSourceRequest(?string $authenticationToken, string $sourceId): ResponseInterface
    {
        return $this->client->makeRequest(
            'DELETE',
            $this->router->generate('user_source_delete', ['sourceId' => $sourceId]),
            $this->createAuthorizationHeader($authenticationToken),
        );
    }

    public function makeGetStatusRequest(): ResponseInterface
    {
        return $this->client->makeRequest('GET', $this->router->generate('status'));
    }

    public function makeGetHealthCheckRequest(): ResponseInterface
    {
        return $this->client->makeRequest('GET', $this->router->generate('health-check'));
    }

    public function makeGetFileSourceFilenamesRequest(?string $authenticationToken, string $sourceId): ResponseInterface
    {
        return $this->client->makeRequest(
            'GET',
            $this->router->generate('file_source_list_filenames', ['sourceId' => $sourceId]),
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
            $this->router->generate('suite_create'),
            $headers,
            http_build_query($payload)
        );
    }

    public function makeGetSuiteRequest(?string $authenticationToken, string $suiteId): ResponseInterface
    {
        return $this->client->makeRequest(
            'GET',
            $this->router->generate('suite_get', ['suiteId' => $suiteId]),
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
            $this->router->generate('suite_update', ['suiteId' => $suiteId]),
            $headers,
            http_build_query($payload)
        );
    }

    public function makeListSuitesRequest(?string $authenticationToken): ResponseInterface
    {
        return $this->client->makeRequest(
            'GET',
            $this->router->generate('suite_list'),
            $this->createAuthorizationHeader($authenticationToken),
        );
    }

    public function makeDeleteSuiteRequest(?string $authenticationToken, string $suiteId): ResponseInterface
    {
        return $this->client->makeRequest(
            'DELETE',
            $this->router->generate('suite_get', ['suiteId' => $suiteId]),
            $this->createAuthorizationHeader($authenticationToken),
        );
    }

    public function makeReadSerializedSuiteRequest(?string $authenticationToken, string $suiteId): ResponseInterface
    {
        return $this->client->makeRequest(
            'GET',
            $this->router->generate('serialized_suite_read', ['serializedSuiteId' => $suiteId]),
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
            $this->router->generate(
                'serialized_suite_create',
                [
                    'suiteId' => $suiteId,
                    'serializedSuiteId' => $serializedSuiteId,
                ]
            ),
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
            $this->router->generate('serialized_suite_get', ['serializedSuiteId' => $serializedSuiteId]),
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
