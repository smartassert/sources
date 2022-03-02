<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Model\EntityId;
use App\Tests\DataProvider\TestConstants;

abstract class AbstractUnauthorizedUserTest extends AbstractApplicationTest
{
    public function testAddFileUnauthorizedUser(): void
    {
        $response = $this->getApplicationClient()->makeAddFileRequest(
            $this->authenticationConfiguration->invalidToken,
            EntityId::create(),
            TestConstants::FILENAME,
            '- content'
        );

        $this->responseAsserter->assertUnauthorizedResponse($response);
    }

    public function testRemoveFileUnauthorizedUser(): void
    {
        $response = $this->getApplicationClient()->makeRemoveFileRequest(
            $this->authenticationConfiguration->invalidToken,
            EntityId::create(),
            TestConstants::FILENAME
        );

        $this->responseAsserter->assertUnauthorizedResponse($response);
    }
}
