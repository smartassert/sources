<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Services\EntityIdFactory;
use App\Tests\Services\AuthenticationConfiguration;

abstract class AbstractUnauthorizedUserTest extends AbstractApplicationTest
{
    private const FILENAME = 'filename.yaml';

    /**
     * @dataProvider unauthorizedUserDataProvider
     */
    public function testAddFileUnauthorizedUser(callable $tokenCreator): void
    {
        $response = $this->applicationClient->makeAddFileRequest(
            $tokenCreator(self::$authenticationConfiguration),
            (new EntityIdFactory())->create(),
            self::FILENAME,
            '- content'
        );

        $this->responseAsserter->assertUnauthorizedResponse($response);
    }

    /**
     * @dataProvider unauthorizedUserDataProvider
     */
    public function testRemoveFileUnauthorizedUser(callable $tokenCreator): void
    {
        $response = $this->applicationClient->makeRemoveFileRequest(
            $tokenCreator(self::$authenticationConfiguration),
            (new EntityIdFactory())->create(),
            self::FILENAME
        );

        $this->responseAsserter->assertUnauthorizedResponse($response);
    }

    /**
     * @dataProvider unauthorizedUserDataProvider
     */
    public function testReadFileUnauthorizedUser(callable $tokenCreator): void
    {
        $response = $this->applicationClient->makeRemoveFileRequest(
            $tokenCreator(self::$authenticationConfiguration),
            (new EntityIdFactory())->create(),
            self::FILENAME
        );

        $this->responseAsserter->assertUnauthorizedResponse($response);
    }

    /**
     * @dataProvider unauthorizedUserDataProvider
     */
    public function testCreateSourceUnauthorizedUser(callable $tokenCreator): void
    {
        $response = $this->applicationClient->makeCreateSourceRequest(
            $tokenCreator(self::$authenticationConfiguration),
            []
        );

        $this->responseAsserter->assertUnauthorizedResponse($response);
    }

    /**
     * @dataProvider unauthorizedUserDataProvider
     */
    public function testListUnauthorizedUser(callable $tokenCreator): void
    {
        $response = $this->applicationClient->makeListSourcesRequest(
            $tokenCreator(self::$authenticationConfiguration),
        );

        $this->responseAsserter->assertUnauthorizedResponse($response);
    }

    /**
     * @dataProvider unauthorizedUserDataProvider
     */
    public function testGetSourceUnauthorizedUser(callable $tokenCreator): void
    {
        $response = $this->applicationClient->makeGetSourceRequest(
            $tokenCreator(self::$authenticationConfiguration),
            (new EntityIdFactory())->create()
        );

        $this->responseAsserter->assertUnauthorizedResponse($response);
    }

    /**
     * @dataProvider unauthorizedUserDataProvider
     */
    public function testUpdateFileSourceUnauthorizedUser(callable $tokenCreator): void
    {
        $response = $this->applicationClient->makeUpdateSourceRequest(
            $tokenCreator(self::$authenticationConfiguration),
            (new EntityIdFactory())->create(),
            []
        );

        $this->responseAsserter->assertUnauthorizedResponse($response);
    }

    /**
     * @dataProvider unauthorizedUserDataProvider
     */
    public function testUpdateGitSourceUnauthorizedUser(callable $tokenCreator): void
    {
        $response = $this->applicationClient->makeUpdateSourceRequest(
            $tokenCreator(self::$authenticationConfiguration),
            (new EntityIdFactory())->create(),
            []
        );

        $this->responseAsserter->assertUnauthorizedResponse($response);
    }

    /**
     * @dataProvider unauthorizedUserDataProvider
     */
    public function testDeleteSourceUnauthorizedUser(callable $tokenCreator): void
    {
        $response = $this->applicationClient->makeDeleteSourceRequest(
            $tokenCreator(self::$authenticationConfiguration),
            (new EntityIdFactory())->create()
        );

        $this->responseAsserter->assertUnauthorizedResponse($response);
    }

    /**
     * @dataProvider unauthorizedUserDataProvider
     */
    public function testReadSourceUnauthorizedUser(callable $tokenCreator): void
    {
        $response = $this->applicationClient->makeReadSourceRequest(
            $tokenCreator(self::$authenticationConfiguration),
            (new EntityIdFactory())->create()
        );

        $this->responseAsserter->assertUnauthorizedResponse($response);
    }

    /**
     * @dataProvider unauthorizedUserDataProvider
     */
    public function testCreateSuiteUnauthorizedUser(callable $tokenCreator): void
    {
        $response = $this->applicationClient->makeCreateSuiteRequest(
            $tokenCreator(self::$authenticationConfiguration),
            []
        );

        $this->responseAsserter->assertUnauthorizedResponse($response);
    }

    /**
     * @dataProvider unauthorizedUserDataProvider
     */
    public function testGetSuiteUnauthorizedUser(callable $tokenCreator): void
    {
        $response = $this->applicationClient->makeGetSuiteRequest(
            $tokenCreator(self::$authenticationConfiguration),
            (new EntityIdFactory())->create()
        );

        $this->responseAsserter->assertUnauthorizedResponse($response);
    }

    /**
     * @dataProvider unauthorizedUserDataProvider
     */
    public function testListSuitesUnauthorizedUser(callable $tokenCreator): void
    {
        $response = $this->applicationClient->makeListSuitesRequest(
            $tokenCreator(self::$authenticationConfiguration)
        );

        $this->responseAsserter->assertUnauthorizedResponse($response);
    }

    /**
     * @dataProvider unauthorizedUserDataProvider
     */
    public function testUpdateSuiteUnauthorizedUser(callable $tokenCreator): void
    {
        $response = $this->applicationClient->makeUpdateSuiteRequest(
            $tokenCreator(self::$authenticationConfiguration),
            (new EntityIdFactory())->create(),
            []
        );

        $this->responseAsserter->assertUnauthorizedResponse($response);
    }

    /**
     * @dataProvider unauthorizedUserDataProvider
     */
    public function testDeleteSuiteUnauthorizedUser(callable $tokenCreator): void
    {
        $response = $this->applicationClient->makeDeleteSuiteRequest(
            $tokenCreator(self::$authenticationConfiguration),
            (new EntityIdFactory())->create(),
        );

        $this->responseAsserter->assertUnauthorizedResponse($response);
    }

    /**
     * @dataProvider unauthorizedUserDataProvider
     */
    public function testCreateSerializedSuiteUnauthorizedUser(callable $tokenCreator): void
    {
        $response = $this->applicationClient->makeCreateSerializedSuiteRequest(
            $tokenCreator(self::$authenticationConfiguration),
            (new EntityIdFactory())->create(),
            [],
        );

        $this->responseAsserter->assertUnauthorizedResponse($response);
    }

    /**
     * @dataProvider unauthorizedUserDataProvider
     */
    public function testReadSerializedSuiteUnauthorizedUser(callable $tokenCreator): void
    {
        $response = $this->applicationClient->makeReadSerializedSuiteRequest(
            $tokenCreator(self::$authenticationConfiguration),
            (new EntityIdFactory())->create(),
        );

        $this->responseAsserter->assertUnauthorizedResponse($response);
    }

    /**
     * @dataProvider unauthorizedUserDataProvider
     */
    public function testGetSerializedSuiteUnauthorizedUser(callable $tokenCreator): void
    {
        $response = $this->applicationClient->makeGetSerializedSuiteRequest(
            $tokenCreator(self::$authenticationConfiguration),
            (new EntityIdFactory())->create(),
        );

        $this->responseAsserter->assertUnauthorizedResponse($response);
    }

    /**
     * @return array<mixed>
     */
    public function unauthorizedUserDataProvider(): array
    {
        return [
            'no token' => [
                'tokenCreator' => function () {
                    return null;
                }
            ],
            'empty token' => [
                'tokenCreator' => function () {
                    return '';
                }
            ],
            'non-empty invalid token' => [
                'tokenCreator' => function (AuthenticationConfiguration $authenticationConfiguration) {
                    return $authenticationConfiguration->getInvalidApiToken();
                }
            ],
        ];
    }
}
