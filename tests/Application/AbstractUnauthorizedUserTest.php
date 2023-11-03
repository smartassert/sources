<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Services\EntityIdFactory;

abstract class AbstractUnauthorizedUserTest extends AbstractApplicationTest
{
    private const FILENAME = 'filename.yaml';

    /**
     * @dataProvider unauthorizedUserDataProvider
     */
    public function testAddFileUnauthorizedUser(?string $token): void
    {
        $response = $this->applicationClient->makeAddFileRequest(
            $token,
            (new EntityIdFactory())->create(),
            self::FILENAME,
            '- content'
        );

        $this->responseAsserter->assertUnauthorizedResponse($response);
    }

    /**
     * @dataProvider unauthorizedUserDataProvider
     */
    public function testRemoveFileUnauthorizedUser(?string $token): void
    {
        $response = $this->applicationClient->makeRemoveFileRequest(
            $token,
            (new EntityIdFactory())->create(),
            self::FILENAME
        );

        $this->responseAsserter->assertUnauthorizedResponse($response);
    }

    /**
     * @dataProvider unauthorizedUserDataProvider
     */
    public function testReadFileUnauthorizedUser(?string $token): void
    {
        $response = $this->applicationClient->makeRemoveFileRequest(
            $token,
            (new EntityIdFactory())->create(),
            self::FILENAME
        );

        $this->responseAsserter->assertUnauthorizedResponse($response);
    }

    /**
     * @dataProvider unauthorizedUserDataProvider
     */
    public function testCreateFileSourceUnauthorizedUser(?string $token): void
    {
        $response = $this->applicationClient->makeCreateFileSourceRequest(
            $token,
            []
        );

        $this->responseAsserter->assertUnauthorizedResponse($response);
    }

    /**
     * @dataProvider unauthorizedUserDataProvider
     */
    public function testCreateGitSourceUnauthorizedUser(?string $token): void
    {
        $response = $this->applicationClient->makeCreateGitSourceRequest(
            $token,
            []
        );

        $this->responseAsserter->assertUnauthorizedResponse($response);
    }

    /**
     * @dataProvider unauthorizedUserDataProvider
     */
    public function testListUnauthorizedUser(?string $token): void
    {
        $response = $this->applicationClient->makeListSourcesRequest(
            $token,
        );

        $this->responseAsserter->assertUnauthorizedResponse($response);
    }

    /**
     * @dataProvider unauthorizedUserDataProvider
     */
    public function testGetSourceUnauthorizedUser(?string $token): void
    {
        $response = $this->applicationClient->makeGetSourceRequest(
            $token,
            (new EntityIdFactory())->create()
        );

        $this->responseAsserter->assertUnauthorizedResponse($response);
    }

    /**
     * @dataProvider unauthorizedUserDataProvider
     */
    public function testUpdateFileSourceUnauthorizedUser(?string $token): void
    {
        $response = $this->applicationClient->makeUpdateFileSourceRequest(
            $token,
            (new EntityIdFactory())->create(),
            []
        );

        $this->responseAsserter->assertUnauthorizedResponse($response);
    }

    /**
     * @dataProvider unauthorizedUserDataProvider
     */
    public function testUpdateGitSourceUnauthorizedUser(?string $token): void
    {
        $response = $this->applicationClient->makeUpdateGitSourceRequest(
            $token,
            (new EntityIdFactory())->create(),
            []
        );

        $this->responseAsserter->assertUnauthorizedResponse($response);
    }

    /**
     * @dataProvider unauthorizedUserDataProvider
     */
    public function testDeleteSourceUnauthorizedUser(?string $token): void
    {
        $response = $this->applicationClient->makeDeleteSourceRequest(
            $token,
            (new EntityIdFactory())->create()
        );

        $this->responseAsserter->assertUnauthorizedResponse($response);
    }

    /**
     * @dataProvider unauthorizedUserDataProvider
     */
    public function testCreateSuiteUnauthorizedUser(?string $token): void
    {
        $response = $this->applicationClient->makeCreateSuiteRequest(
            $token,
            []
        );

        $this->responseAsserter->assertUnauthorizedResponse($response);
    }

    /**
     * @dataProvider unauthorizedUserDataProvider
     */
    public function testGetSuiteUnauthorizedUser(?string $token): void
    {
        $response = $this->applicationClient->makeGetSuiteRequest(
            $token,
            (new EntityIdFactory())->create()
        );

        $this->responseAsserter->assertUnauthorizedResponse($response);
    }

    /**
     * @dataProvider unauthorizedUserDataProvider
     */
    public function testListSuitesUnauthorizedUser(?string $token): void
    {
        $response = $this->applicationClient->makeListSuitesRequest($token);

        $this->responseAsserter->assertUnauthorizedResponse($response);
    }

    /**
     * @dataProvider unauthorizedUserDataProvider
     */
    public function testUpdateSuiteUnauthorizedUser(?string $token): void
    {
        $response = $this->applicationClient->makeUpdateSuiteRequest(
            $token,
            (new EntityIdFactory())->create(),
            []
        );

        $this->responseAsserter->assertUnauthorizedResponse($response);
    }

    /**
     * @dataProvider unauthorizedUserDataProvider
     */
    public function testDeleteSuiteUnauthorizedUser(?string $token): void
    {
        $response = $this->applicationClient->makeDeleteSuiteRequest(
            $token,
            (new EntityIdFactory())->create(),
        );

        $this->responseAsserter->assertUnauthorizedResponse($response);
    }

    /**
     * @dataProvider unauthorizedUserDataProvider
     */
    public function testCreateSerializedSuiteUnauthorizedUser(?string $token): void
    {
        $response = $this->applicationClient->makeCreateSerializedSuiteRequest(
            $token,
            (new EntityIdFactory())->create(),
            (new EntityIdFactory())->create(),
            [],
        );

        $this->responseAsserter->assertUnauthorizedResponse($response);
    }

    /**
     * @dataProvider unauthorizedUserDataProvider
     */
    public function testReadSerializedSuiteUnauthorizedUser(?string $token): void
    {
        $response = $this->applicationClient->makeReadSerializedSuiteRequest(
            $token,
            (new EntityIdFactory())->create(),
        );

        $this->responseAsserter->assertUnauthorizedResponse($response);
    }

    /**
     * @dataProvider unauthorizedUserDataProvider
     */
    public function testGetSerializedSuiteUnauthorizedUser(?string $token): void
    {
        $response = $this->applicationClient->makeGetSerializedSuiteRequest(
            $token,
            (new EntityIdFactory())->create(),
        );

        $this->responseAsserter->assertUnauthorizedResponse($response);
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
