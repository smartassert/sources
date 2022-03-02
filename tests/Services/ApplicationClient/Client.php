<?php

declare(strict_types=1);

namespace App\Tests\Services\ApplicationClient;

use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Routing\RouterInterface;

class Client
{
    public function __construct(
        private AdapterInterface $client,
        private RouterInterface $router,
    ) {
    }

    public function makeAddFileRequest(
        string $authenticationToken,
        string $sourceId,
        string $filename,
        string $content
    ): ResponseInterface {
        return $this->client->makeRequest(
            'POST',
            $this->router->generate('file_source_file_add', [
                'sourceId' => $sourceId,
                'filename' => $filename,
            ]),
            [
                'authorization' => 'Bearer ' . $authenticationToken,
            ],
            $content
        );
    }

    public function makeReadFileRequest(
        string $authenticationToken,
        string $sourceId,
        string $filename
    ): ResponseInterface {
        return $this->client->makeRequest(
            'GET',
            $this->router->generate('file_source_file_read', [
                'sourceId' => $sourceId,
                'filename' => $filename,
            ]),
            [
                'authorization' => 'Bearer ' . $authenticationToken,
            ]
        );
    }

    public function makeRemoveFileRequest(
        string $authenticationToken,
        string $sourceId,
        string $filename
    ): ResponseInterface {
        return $this->client->makeRequest(
            'DELETE',
            $this->router->generate('file_source_file_remove', [
                'sourceId' => $sourceId,
                'filename' => $filename,
            ]),
            [
                'authorization' => 'Bearer ' . $authenticationToken,
            ]
        );
    }

    /**
     * @param array<string, string> $payload
     */
    public function makeCreateSourceRequest(string $authenticationToken, array $payload): ResponseInterface
    {
        return $this->client->makeRequest(
            'POST',
            $this->router->generate('source_create'),
            [
                'authorization' => 'Bearer ' . $authenticationToken,
                'content-type' => 'application/x-www-form-urlencoded',
            ],
            http_build_query($payload)
        );
    }

    public function makeListSourcesRequest(string $authenticationToken): ResponseInterface
    {
        return $this->client->makeRequest(
            'GET',
            $this->router->generate('source_list'),
            [
                'authorization' => 'Bearer ' . $authenticationToken,
            ]
        );
    }

    public function makeGetSourceRequest(string $authenticationToken, string $sourceId): ResponseInterface
    {
        return $this->client->makeRequest(
            'GET',
            $this->router->generate('user_source_get', ['sourceId' => $sourceId]),
            [
                'authorization' => 'Bearer ' . $authenticationToken,
            ]
        );
    }

    /**
     * @param array<string, string> $payload
     */
    public function makeUpdateSourceRequest(
        string $authenticationToken,
        string $sourceId,
        array $payload
    ): ResponseInterface {
        return $this->client->makeRequest(
            'PUT',
            $this->router->generate('user_source_update', ['sourceId' => $sourceId]),
            [
                'authorization' => 'Bearer ' . $authenticationToken,
                'content-type' => 'application/x-www-form-urlencoded',
            ],
            http_build_query($payload)
        );
    }

    public function makeDeleteSourceRequest(string $authenticationToken, string $sourceId): ResponseInterface
    {
        return $this->client->makeRequest(
            'DELETE',
            $this->router->generate('user_source_delete', ['sourceId' => $sourceId]),
            [
                'authorization' => 'Bearer ' . $authenticationToken,
            ]
        );
    }

    /**
     * @param array<string, string> $payload
     */
    public function makePrepareSourceRequest(
        string $authenticationToken,
        string $sourceId,
        array $payload
    ): ResponseInterface {
        return $this->client->makeRequest(
            'POST',
            $this->router->generate('user_source_prepare', ['sourceId' => $sourceId]),
            [
                'authorization' => 'Bearer ' . $authenticationToken,
                'content-type' => 'application/x-www-form-urlencoded',
            ],
            http_build_query($payload)
        );
    }

    public function makeReadSourceRequest(string $authenticationToken, string $sourceId): ResponseInterface
    {
        return $this->client->makeRequest(
            'GET',
            $this->router->generate('user_source_read', ['sourceId' => $sourceId]),
            [
                'authorization' => 'Bearer ' . $authenticationToken,
            ]
        );
    }
}
