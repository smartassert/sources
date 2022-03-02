<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Model\EntityId;
use App\Tests\DataProvider\TestConstants;

abstract class AbstractUnauthorizedUserTest extends AbstractApplicationTest
{
    public function testAddFileUnauthorizedUser(): void
    {
        $response = $this->applicationClient->makeAddFileRequest(
            $this->authenticationConfiguration->invalidToken,
            EntityId::create(),
            TestConstants::FILENAME,
            '- content'
        );

        $this->responseAsserter->assertUnauthorizedResponse($response);
    }

    public function testRemoveFileUnauthorizedUser(): void
    {
        $response = $this->applicationClient->makeRemoveFileRequest(
            $this->authenticationConfiguration->invalidToken,
            EntityId::create(),
            TestConstants::FILENAME
        );

        $this->responseAsserter->assertUnauthorizedResponse($response);
    }

    public function testReadFileUnauthorizedUser(): void
    {
        $response = $this->applicationClient->makeRemoveFileRequest(
            $this->authenticationConfiguration->invalidToken,
            EntityId::create(),
            TestConstants::FILENAME
        );

        $this->responseAsserter->assertUnauthorizedResponse($response);
    }

    public function testCreateSourceUnauthorizedUser(): void
    {
        $response = $this->applicationClient->makeCreateSourceRequest(
            $this->authenticationConfiguration->invalidToken,
            []
        );

        $this->responseAsserter->assertUnauthorizedResponse($response);
    }

    public function testListUnauthorizedUser(): void
    {
        $response = $this->applicationClient->makeListSourcesRequest(
            $this->authenticationConfiguration->invalidToken
        );

        $this->responseAsserter->assertUnauthorizedResponse($response);
    }

    public function testGetSourceUnauthorizedUser(): void
    {
        $response = $this->applicationClient->makeGetSourceRequest(
            $this->authenticationConfiguration->invalidToken,
            EntityId::create()
        );

        $this->responseAsserter->assertUnauthorizedResponse($response);
    }

    public function testUpdateSourceUnauthorizedUser(): void
    {
        $response = $this->applicationClient->makeUpdateSourceRequest(
            $this->authenticationConfiguration->invalidToken,
            EntityId::create(),
            []
        );

        $this->responseAsserter->assertUnauthorizedResponse($response);
    }

    public function testDeleteSourceUnauthorizedUser(): void
    {
        $response = $this->applicationClient->makeDeleteSourceRequest(
            $this->authenticationConfiguration->invalidToken,
            EntityId::create()
        );

        $this->responseAsserter->assertUnauthorizedResponse($response);
    }

    public function testPrepareSourceUnauthorizedUser(): void
    {
        $response = $this->applicationClient->makePrepareSourceRequest(
            $this->authenticationConfiguration->invalidToken,
            EntityId::create(),
            []
        );

        $this->responseAsserter->assertUnauthorizedResponse($response);
    }

    public function testReadSourceUnauthorizedUser(): void
    {
        $response = $this->applicationClient->makeReadSourceRequest(
            $this->authenticationConfiguration->invalidToken,
            EntityId::create()
        );

        $this->responseAsserter->assertUnauthorizedResponse($response);
    }
}
