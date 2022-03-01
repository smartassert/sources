<?php

declare(strict_types=1);

namespace App\Tests\Services\ApplicationClient;

use Psr\Http\Message\ResponseInterface;

class Client
{
    public function __construct(
        private AdapterInterface $client,
        private Routes $routes,
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
            $this->createUrl($this->routes->addFileUrl, [
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
            $this->createUrl($this->routes->readFileUrl, [
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
            $this->createUrl($this->routes->removeFileUrl, [
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
            $this->createUrl($this->routes->createSourceUrl),
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
            $this->createUrl($this->routes->listSourcesUrl),
            [
                'authorization' => 'Bearer ' . $authenticationToken,
            ]
        );
    }

    public function makeGetSourceRequest(string $authenticationToken, string $sourceId): ResponseInterface
    {
        return $this->client->makeRequest(
            'GET',
            $this->createUrl($this->routes->getSourceUrl, ['sourceId' => $sourceId]),
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
            $this->createUrl($this->routes->updateSourceUrl, ['sourceId' => $sourceId]),
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
            $this->createUrl($this->routes->deleteSourceUrl, ['sourceId' => $sourceId]),
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
            $this->createUrl($this->routes->prepareSourceUrl, ['sourceId' => $sourceId]),
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
            $this->createUrl($this->routes->readSourceUrl, ['sourceId' => $sourceId]),
            [
                'authorization' => 'Bearer ' . $authenticationToken,
            ]
        );
    }

    /**
     * @param array<string, string> $parameters
     */
    private function createUrl(string $template, array $parameters = []): string
    {
        $url = $template;

        foreach ($parameters as $key => $value) {
            $url = str_replace('{{ ' . $key . ' }}', $value, $url);
        }

        return $url;
    }
}
