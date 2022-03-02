<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\FileSource;
use App\Model\EntityId;
use App\Services\Source\Store;
use App\Tests\DataProvider\AddFileInvalidRequestDataProviderTrait;
use App\Tests\DataProvider\TestConstants;
use App\Tests\DataProvider\YamlFileInvalidRequestDataProviderTrait;
use App\Tests\Model\UserId;
use App\Tests\Services\EntityRemover;

class AddReadDeleteFileTest extends AbstractIntegrationTest
{
    use AddFileInvalidRequestDataProviderTrait;
    use YamlFileInvalidRequestDataProviderTrait;

    private FileSource $source;
    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $entityRemover = self::getContainer()->get(EntityRemover::class);
        if ($entityRemover instanceof EntityRemover) {
            $entityRemover->removeAll();
        }

        $store = self::getContainer()->get(Store::class);
        \assert($store instanceof Store);
        $this->store = $store;

        $this->source = new FileSource($this->authenticationConfiguration->authenticatedUserId, '');
        $this->store->add($this->source);
    }

    public function testAddFileInvalidSourceUser(): void
    {
        $source = new FileSource(UserId::create(), '');
        $this->store->add($source);

        $response = $this->applicationClient->makeAddFileRequest(
            $this->validToken,
            $source->getId(),
            TestConstants::FILENAME,
            '- content'
        );

        $this->responseAsserter->assertForbiddenResponse($response);
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
            $this->validToken,
            $this->source->getId(),
            $filename,
            $content
        );

        $this->responseAsserter->assertInvalidRequestJsonResponse($response, $expectedResponseData);
    }

    public function testReadFileUnauthorizedUser(): void
    {
        $response = $this->applicationClient->makeRemoveFileRequest(
            $this->invalidToken,
            EntityId::create(),
            TestConstants::FILENAME
        );

        $this->responseAsserter->assertUnauthorizedResponse($response);
    }

    public function testReadFileInvalidSourceUser(): void
    {
        $source = new FileSource(UserId::create(), '');
        $this->store->add($source);

        $response = $this->applicationClient->makeReadFileRequest(
            $this->validToken,
            $source->getId(),
            TestConstants::FILENAME
        );

        $this->responseAsserter->assertForbiddenResponse($response);
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
            $this->validToken,
            $this->source->getId(),
            $filename
        );

        $this->responseAsserter->assertInvalidRequestJsonResponse($response, $expectedResponseData);
    }

    public function testRemoveFileInvalidSourceUser(): void
    {
        $source = new FileSource(UserId::create(), '');
        $this->store->add($source);

        $response = $this->applicationClient->makeRemoveFileRequest(
            $this->validToken,
            $source->getId(),
            TestConstants::FILENAME
        );

        $this->responseAsserter->assertForbiddenResponse($response);
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
            $this->validToken,
            $this->source->getId(),
            $filename
        );

        $this->responseAsserter->assertInvalidRequestJsonResponse($response, $expectedResponseData);
    }

    public function testAddReadRemoveFileSuccess(): void
    {
        $initialContent = '- initial content';
        $updatedContent = '- updated content';

        $addResponse = $this->applicationClient->makeAddFileRequest(
            $this->validToken,
            $this->source->getId(),
            TestConstants::FILENAME,
            $initialContent
        );

        $this->responseAsserter->assertSuccessfulResponseWithNoBody($addResponse);

        $initialReadResponse = $this->applicationClient->makeReadFileRequest(
            $this->validToken,
            $this->source->getId(),
            TestConstants::FILENAME
        );

        $this->responseAsserter->assertReadSourceSuccessResponse($initialReadResponse, $initialContent);

        $updateResponse = $this->applicationClient->makeAddFileRequest(
            $this->validToken,
            $this->source->getId(),
            TestConstants::FILENAME,
            $updatedContent
        );

        $this->responseAsserter->assertSuccessfulResponseWithNoBody($updateResponse);

        $updatedReadResponse = $this->applicationClient->makeReadFileRequest(
            $this->validToken,
            $this->source->getId(),
            TestConstants::FILENAME
        );

        $this->responseAsserter->assertReadSourceSuccessResponse($updatedReadResponse, $updatedContent);

        $removeResponse = $this->applicationClient->makeRemoveFileRequest(
            $this->validToken,
            $this->source->getId(),
            TestConstants::FILENAME
        );

        $this->responseAsserter->assertSuccessfulResponseWithNoBody($removeResponse);

        $notFoundReadResponse = $this->applicationClient->makeReadFileRequest(
            $this->validToken,
            $this->source->getId(),
            TestConstants::FILENAME
        );

        $this->responseAsserter->assertNotFoundResponse($notFoundReadResponse);
    }
}
