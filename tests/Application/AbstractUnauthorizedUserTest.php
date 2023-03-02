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
    public function testCreateFileSourceUnauthorizedUser(callable $tokenCreator): void
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
    public function testCreateGitSourceUnauthorizedUser(callable $tokenCreator): void
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
        $response = $this->applicationClient->makeUpdateFileSourceRequest(
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
        $response = $this->applicationClient->makeUpdateGitSourceRequest(
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
    public function testPrepareSourceUnauthorizedUser(callable $tokenCreator): void
    {
        $response = $this->applicationClient->makePrepareSourceRequest(
            $tokenCreator(self::$authenticationConfiguration),
            (new EntityIdFactory())->create(),
            []
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
