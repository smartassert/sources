<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Model\EntityId;
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
            $tokenCreator($this->authenticationConfiguration),
            EntityId::create(),
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
            $tokenCreator($this->authenticationConfiguration),
            EntityId::create(),
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
            $tokenCreator($this->authenticationConfiguration),
            EntityId::create(),
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
            $tokenCreator($this->authenticationConfiguration),
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
            $tokenCreator($this->authenticationConfiguration),
        );

        $this->responseAsserter->assertUnauthorizedResponse($response);
    }

    /**
     * @dataProvider unauthorizedUserDataProvider
     */
    public function testGetSourceUnauthorizedUser(callable $tokenCreator): void
    {
        $response = $this->applicationClient->makeGetSourceRequest(
            $tokenCreator($this->authenticationConfiguration),
            EntityId::create()
        );

        $this->responseAsserter->assertUnauthorizedResponse($response);
    }

    /**
     * @dataProvider unauthorizedUserDataProvider
     */
    public function testUpdateSourceUnauthorizedUser(callable $tokenCreator): void
    {
        $response = $this->applicationClient->makeUpdateSourceRequest(
            $tokenCreator($this->authenticationConfiguration),
            EntityId::create(),
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
            $tokenCreator($this->authenticationConfiguration),
            EntityId::create()
        );

        $this->responseAsserter->assertUnauthorizedResponse($response);
    }

    /**
     * @dataProvider unauthorizedUserDataProvider
     */
    public function testPrepareSourceUnauthorizedUser(callable $tokenCreator): void
    {
        $response = $this->applicationClient->makePrepareSourceRequest(
            $tokenCreator($this->authenticationConfiguration),
            EntityId::create(),
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
            $tokenCreator($this->authenticationConfiguration),
            EntityId::create()
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
                    return $authenticationConfiguration->invalidToken;
                }
            ],
        ];
    }
}
