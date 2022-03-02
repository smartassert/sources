<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\FileSource;
use App\Entity\RunSource;
use App\Services\Source\Store;
use App\Tests\DataProvider\TestConstants;
use App\Tests\Model\UserId;

abstract class AbstractInvalidSourceUserTest extends AbstractApplicationTest
{
    private FileSource $fileSource;
    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $store = self::getContainer()->get(Store::class);
        \assert($store instanceof Store);
        $this->store = $store;

        $this->fileSource = new FileSource(UserId::create(), '');
        $store->add($this->fileSource);
    }

    public function testAddFileInvalidUser(): void
    {
        $response = $this->applicationClient->makeAddFileRequest(
            $this->authenticationConfiguration->validToken,
            $this->fileSource->getId(),
            TestConstants::FILENAME,
            '- content'
        );

        $this->responseAsserter->assertForbiddenResponse($response);
    }

    public function testRemoveFileInvalidUser(): void
    {
        $response = $this->applicationClient->makeRemoveFileRequest(
            $this->authenticationConfiguration->validToken,
            $this->fileSource->getId(),
            TestConstants::FILENAME
        );

        $this->responseAsserter->assertForbiddenResponse($response);
    }

    public function testReadFileInvalidUser(): void
    {
        $response = $this->applicationClient->makeRemoveFileRequest(
            $this->authenticationConfiguration->validToken,
            $this->fileSource->getId(),
            TestConstants::FILENAME
        );

        $this->responseAsserter->assertForbiddenResponse($response);
    }

    public function testGetSourceInvalidUser(): void
    {
        $response = $this->applicationClient->makeGetSourceRequest(
            $this->authenticationConfiguration->validToken,
            $this->fileSource->getId()
        );

        $this->responseAsserter->assertForbiddenResponse($response);
    }

    public function testUpdateSourceInvalidUser(): void
    {
        $response = $this->applicationClient->makeUpdateSourceRequest(
            $this->authenticationConfiguration->validToken,
            $this->fileSource->getId(),
            []
        );

        $this->responseAsserter->assertForbiddenResponse($response);
    }

    public function testDeleteSourceInvalidUser(): void
    {
        $response = $this->applicationClient->makeDeleteSourceRequest(
            $this->authenticationConfiguration->validToken,
            $this->fileSource->getId()
        );

        $this->responseAsserter->assertForbiddenResponse($response);
    }

    public function testPrepareSourceInvalidUser(): void
    {
        $response = $this->applicationClient->makePrepareSourceRequest(
            $this->authenticationConfiguration->validToken,
            $this->fileSource->getId(),
            []
        );

        $this->responseAsserter->assertForbiddenResponse($response);
    }

    public function testReadSourceInvalidUser(): void
    {
        $runSource = new RunSource($this->fileSource);
        $this->store->add($runSource);

        $response = $this->applicationClient->makeReadSourceRequest(
            $this->authenticationConfiguration->validToken,
            $runSource->getId()
        );

        $this->responseAsserter->assertForbiddenResponse($response);
    }
}
