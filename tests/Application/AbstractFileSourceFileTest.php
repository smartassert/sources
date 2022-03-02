<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\FileSource;
use App\Services\Source\Store;
use App\Tests\DataProvider\AddFileInvalidRequestDataProviderTrait;
use App\Tests\DataProvider\TestConstants;
use App\Tests\DataProvider\YamlFileInvalidRequestDataProviderTrait;

abstract class AbstractFileSourceFileTest extends AbstractApplicationTest
{
    use AddFileInvalidRequestDataProviderTrait;
    use YamlFileInvalidRequestDataProviderTrait;

    private FileSource $fileSource;

    protected function setUp(): void
    {
        parent::setUp();

        $store = self::getContainer()->get(Store::class);
        \assert($store instanceof Store);

        $this->fileSource = new FileSource($this->authenticationConfiguration->authenticatedUserId, '');
        $store->add($this->fileSource);
    }

    /**
     * @dataProvider addFileInvalidRequestDataProvider
     *
     * @param array<mixed> $expectedResponseData
     */
    public function testAddFileInvalidRequest(
        string $filename,
        string $content,
        array $expectedResponseData
    ): void {
        $response = $this->applicationClient->makeAddFileRequest(
            $this->authenticationConfiguration->validToken,
            $this->fileSource->getId(),
            $filename,
            $content
        );

        $this->responseAsserter->assertInvalidRequestJsonResponse($response, $expectedResponseData);
    }

    /**
     * @dataProvider yamlFileInvalidRequestDataProvider
     *
     * @param array<mixed> $expectedResponseData
     */
    public function testReadFileInvalidRequest(
        string $filename,
        array $expectedResponseData
    ): void {
        $response = $this->applicationClient->makeReadFileRequest(
            $this->authenticationConfiguration->validToken,
            $this->fileSource->getId(),
            $filename
        );

        $this->responseAsserter->assertInvalidRequestJsonResponse($response, $expectedResponseData);
    }

    /**
     * @dataProvider yamlFileInvalidRequestDataProvider
     *
     * @param array<mixed> $expectedResponseData
     */
    public function testRemoveFileInvalidRequest(
        string $filename,
        array $expectedResponseData
    ): void {
        $response = $this->applicationClient->makeRemoveFileRequest(
            $this->authenticationConfiguration->validToken,
            $this->fileSource->getId(),
            $filename
        );

        $this->responseAsserter->assertInvalidRequestJsonResponse($response, $expectedResponseData);
    }

    public function testRemoveFileNotFound(): void
    {
        $response = $this->applicationClient->makeRemoveFileRequest(
            $this->authenticationConfiguration->validToken,
            $this->fileSource->getId(),
            TestConstants::FILENAME
        );

        $this->responseAsserter->assertSuccessfulResponseWithNoBody($response);
    }

    public function testReadFileNotFound(): void
    {
        $response = $this->applicationClient->makeReadFileRequest(
            $this->authenticationConfiguration->validToken,
            $this->fileSource->getId(),
            TestConstants::FILENAME
        );

        $this->responseAsserter->assertNotFoundResponse($response);
    }

    public function testAddReadUpdateRemoveFileSuccess(): void
    {
        $initialContent = '- initial content';
        $updatedContent = '- updated content';

        $addResponse = $this->applicationClient->makeAddFileRequest(
            $this->authenticationConfiguration->validToken,
            $this->fileSource->getId(),
            TestConstants::FILENAME,
            $initialContent
        );

        $this->responseAsserter->assertSuccessfulResponseWithNoBody($addResponse);

        $initialReadResponse = $this->applicationClient->makeReadFileRequest(
            $this->authenticationConfiguration->validToken,
            $this->fileSource->getId(),
            TestConstants::FILENAME
        );

        $this->responseAsserter->assertReadSourceSuccessResponse($initialReadResponse, $initialContent);

        $updateResponse = $this->applicationClient->makeAddFileRequest(
            $this->authenticationConfiguration->validToken,
            $this->fileSource->getId(),
            TestConstants::FILENAME,
            $updatedContent
        );

        $this->responseAsserter->assertSuccessfulResponseWithNoBody($updateResponse);

        $updatedReadResponse = $this->applicationClient->makeReadFileRequest(
            $this->authenticationConfiguration->validToken,
            $this->fileSource->getId(),
            TestConstants::FILENAME
        );

        $this->responseAsserter->assertReadSourceSuccessResponse($updatedReadResponse, $updatedContent);

        $removeResponse = $this->applicationClient->makeRemoveFileRequest(
            $this->authenticationConfiguration->validToken,
            $this->fileSource->getId(),
            TestConstants::FILENAME
        );

        $this->responseAsserter->assertSuccessfulResponseWithNoBody($removeResponse);

        $notFoundReadResponse = $this->applicationClient->makeReadFileRequest(
            $this->authenticationConfiguration->validToken,
            $this->fileSource->getId(),
            TestConstants::FILENAME
        );

        $this->responseAsserter->assertNotFoundResponse($notFoundReadResponse);
    }
}
