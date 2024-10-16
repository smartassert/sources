<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Services\EntityIdFactory;
use PHPUnit\Framework\Attributes\DataProvider;

abstract class AbstractUnauthorizedUserTest extends AbstractApplicationTest
{
    private const FILENAME = 'filename.yaml';

    #[DataProvider('unauthorizedUserDataProvider')]
    public function testAddFileUnauthorizedUser(?string $token): void
    {
        $response = $this->applicationClient->makeAddFileRequest(
            $token,
            (new EntityIdFactory())->create(),
            self::FILENAME,
            '- content'
        );

        self::assertSame(401, $response->getStatusCode());
        self::assertSame('', $response->getBody()->getContents());
    }

    #[DataProvider('unauthorizedUserDataProvider')]
    public function testRemoveFileUnauthorizedUser(?string $token): void
    {
        $response = $this->applicationClient->makeRemoveFileRequest(
            $token,
            (new EntityIdFactory())->create(),
            self::FILENAME
        );

        self::assertSame(401, $response->getStatusCode());
        self::assertSame('', $response->getBody()->getContents());
    }

    #[DataProvider('unauthorizedUserDataProvider')]
    public function testReadFileUnauthorizedUser(?string $token): void
    {
        $response = $this->applicationClient->makeRemoveFileRequest(
            $token,
            (new EntityIdFactory())->create(),
            self::FILENAME
        );

        self::assertSame(401, $response->getStatusCode());
        self::assertSame('', $response->getBody()->getContents());
    }

    #[DataProvider('unauthorizedUserDataProvider')]
    public function testCreateFileSourceUnauthorizedUser(?string $token): void
    {
        $response = $this->applicationClient->makeCreateFileSourceRequest(
            $token,
            []
        );

        self::assertSame(401, $response->getStatusCode());
        self::assertSame('', $response->getBody()->getContents());
    }

    #[DataProvider('unauthorizedUserDataProvider')]
    public function testCreateGitSourceUnauthorizedUser(?string $token): void
    {
        $response = $this->applicationClient->makeCreateGitSourceRequest(
            $token,
            []
        );

        self::assertSame(401, $response->getStatusCode());
        self::assertSame('', $response->getBody()->getContents());
    }

    #[DataProvider('unauthorizedUserDataProvider')]
    public function testListUnauthorizedUser(?string $token): void
    {
        $response = $this->applicationClient->makeListSourcesRequest(
            $token,
        );

        self::assertSame(401, $response->getStatusCode());
        self::assertSame('', $response->getBody()->getContents());
    }

    #[DataProvider('unauthorizedUserDataProvider')]
    public function testGetSourceUnauthorizedUser(?string $token): void
    {
        $response = $this->applicationClient->makeGetSourceRequest(
            $token,
            (new EntityIdFactory())->create()
        );

        self::assertSame(401, $response->getStatusCode());
        self::assertSame('', $response->getBody()->getContents());
    }

    #[DataProvider('unauthorizedUserDataProvider')]
    public function testUpdateFileSourceUnauthorizedUser(?string $token): void
    {
        $response = $this->applicationClient->makeUpdateFileSourceRequest(
            $token,
            (new EntityIdFactory())->create(),
            []
        );

        self::assertSame(401, $response->getStatusCode());
        self::assertSame('', $response->getBody()->getContents());
    }

    #[DataProvider('unauthorizedUserDataProvider')]
    public function testUpdateGitSourceUnauthorizedUser(?string $token): void
    {
        $response = $this->applicationClient->makeUpdateGitSourceRequest(
            $token,
            (new EntityIdFactory())->create(),
            []
        );

        self::assertSame(401, $response->getStatusCode());
        self::assertSame('', $response->getBody()->getContents());
    }

    #[DataProvider('unauthorizedUserDataProvider')]
    public function testDeleteSourceUnauthorizedUser(?string $token): void
    {
        $response = $this->applicationClient->makeDeleteSourceRequest(
            $token,
            (new EntityIdFactory())->create()
        );

        self::assertSame(401, $response->getStatusCode());
        self::assertSame('', $response->getBody()->getContents());
    }

    #[DataProvider('unauthorizedUserDataProvider')]
    public function testCreateSuiteUnauthorizedUser(?string $token): void
    {
        $response = $this->applicationClient->makeCreateSuiteRequest(
            $token,
            []
        );

        self::assertSame(401, $response->getStatusCode());
        self::assertSame('', $response->getBody()->getContents());
    }

    #[DataProvider('unauthorizedUserDataProvider')]
    public function testGetSuiteUnauthorizedUser(?string $token): void
    {
        $response = $this->applicationClient->makeGetSuiteRequest(
            $token,
            (new EntityIdFactory())->create()
        );

        self::assertSame(401, $response->getStatusCode());
        self::assertSame('', $response->getBody()->getContents());
    }

    #[DataProvider('unauthorizedUserDataProvider')]
    public function testListSuitesUnauthorizedUser(?string $token): void
    {
        $response = $this->applicationClient->makeListSuitesRequest($token);

        self::assertSame(401, $response->getStatusCode());
        self::assertSame('', $response->getBody()->getContents());
    }

    #[DataProvider('unauthorizedUserDataProvider')]
    public function testUpdateSuiteUnauthorizedUser(?string $token): void
    {
        $response = $this->applicationClient->makeUpdateSuiteRequest(
            $token,
            (new EntityIdFactory())->create(),
            []
        );

        self::assertSame(401, $response->getStatusCode());
        self::assertSame('', $response->getBody()->getContents());
    }

    #[DataProvider('unauthorizedUserDataProvider')]
    public function testDeleteSuiteUnauthorizedUser(?string $token): void
    {
        $response = $this->applicationClient->makeDeleteSuiteRequest(
            $token,
            (new EntityIdFactory())->create(),
        );

        self::assertSame(401, $response->getStatusCode());
        self::assertSame('', $response->getBody()->getContents());
    }

    #[DataProvider('unauthorizedUserDataProvider')]
    public function testCreateSerializedSuiteUnauthorizedUser(?string $token): void
    {
        $response = $this->applicationClient->makeCreateSerializedSuiteRequest(
            $token,
            (new EntityIdFactory())->create(),
            (new EntityIdFactory())->create(),
            [],
        );

        self::assertSame(401, $response->getStatusCode());
        self::assertSame('', $response->getBody()->getContents());
    }

    #[DataProvider('unauthorizedUserDataProvider')]
    public function testReadSerializedSuiteUnauthorizedUser(?string $token): void
    {
        $response = $this->applicationClient->makeReadSerializedSuiteRequest(
            $token,
            (new EntityIdFactory())->create(),
        );

        self::assertSame(401, $response->getStatusCode());
        self::assertSame('', $response->getBody()->getContents());
    }

    #[DataProvider('unauthorizedUserDataProvider')]
    public function testGetSerializedSuiteUnauthorizedUser(?string $token): void
    {
        $response = $this->applicationClient->makeGetSerializedSuiteRequest(
            $token,
            (new EntityIdFactory())->create(),
        );

        self::assertSame(401, $response->getStatusCode());
        self::assertSame('', $response->getBody()->getContents());
    }

    /**
     * @return array<mixed>
     */
    public static function unauthorizedUserDataProvider(): array
    {
        return [
            'no token' => [
                'token' => null,
            ],
            'empty token' => [
                'token' => '',
            ],
            'non-empty invalid token' => [
                'token' => 'invalid api token',
            ],
        ];
    }
}
