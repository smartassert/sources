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
}
