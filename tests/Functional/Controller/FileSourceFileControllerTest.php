<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\FileSource;
use App\Model\EntityId;
use App\Services\Source\Store;
use App\Tests\DataProvider\AddFileInvalidRequestDataProviderTrait;
use App\Tests\DataProvider\TestConstants;
use App\Tests\DataProvider\YamlFileInvalidRequestDataProviderTrait;
use App\Tests\Model\UserId;
use League\Flysystem\FilesystemOperator;

class FileSourceFileControllerTest extends AbstractSourceControllerTest
{
    use AddFileInvalidRequestDataProviderTrait;
    use YamlFileInvalidRequestDataProviderTrait;

    private FilesystemOperator $fileSourceStorage;

    private FileSource $fileSource;
    private Store $store;
    private string $sourceRelativePath;

    protected function setUp(): void
    {
        parent::setUp();

        $fileSourceStorage = self::getContainer()->get('file_source.storage');
        \assert($fileSourceStorage instanceof FilesystemOperator);
        $this->fileSourceStorage = $fileSourceStorage;

        $this->fileSource = new FileSource(
            $this->authenticationConfiguration->authenticatedUserId,
            'file source label'
        );
        $this->sourceRelativePath = $this->fileSource->getDirectoryPath();

        $store = self::getContainer()->get(Store::class);
        \assert($store instanceof Store);
        $this->store = $store;

        $store->add($this->fileSource);
    }

    public function testAddFileUnauthorizedUser(): void
    {
        $response = $this->applicationClient->makeAddFileRequest(
            $this->invalidToken,
            $this->fileSource->getId(),
            TestConstants::FILENAME,
            '- content'
        );

        $this->responseAsserter->assertUnauthorizedResponse($response);
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
            $this->fileSource->getId(),
            $filename,
            $content
        );

        $this->responseAsserter->assertInvalidRequestJsonResponse($response, $expectedResponseData);
    }

    public function testAddFileSuccess(): void
    {
        $filename = TestConstants::FILENAME;
        $content = '- file content';
        $fileRelativePath = $this->sourceRelativePath . '/' . $filename;

        self::assertFalse($this->fileSourceStorage->directoryExists($this->sourceRelativePath));
        self::assertFalse($this->fileSourceStorage->fileExists($fileRelativePath));

        $response = $this->applicationClient->makeAddFileRequest(
            $this->validToken,
            $this->fileSource->getId(),
            $filename,
            $content
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertTrue($this->fileSourceStorage->directoryExists($this->sourceRelativePath));
        self::assertTrue($this->fileSourceStorage->fileExists($fileRelativePath));
        self::assertSame($content, $this->fileSourceStorage->read($fileRelativePath));
    }

    public function testUpdateFileSuccess(): void
    {
        $filename = TestConstants::FILENAME;
        $initialContent = '- initial content';
        $updatedContent = '- updated content';
        $fileRelativePath = $this->sourceRelativePath . '/' . $filename;

        $this->fileSourceStorage->write($fileRelativePath, $initialContent);

        $response = $this->applicationClient->makeAddFileRequest(
            $this->validToken,
            $this->fileSource->getId(),
            $filename,
            $updatedContent
        );

        $this->responseAsserter->assertSuccessfulResponseWithNoBody($response);
        self::assertTrue($this->fileSourceStorage->directoryExists($this->sourceRelativePath));
        self::assertTrue($this->fileSourceStorage->fileExists($fileRelativePath));
        self::assertSame($updatedContent, $this->fileSourceStorage->read($fileRelativePath));
    }

    public function testRemoveFileUnauthorizedUser(): void
    {
        $response = $this->applicationClient->makeRemoveFileRequest(
            $this->invalidToken,
            $this->fileSource->getId(),
            TestConstants::FILENAME
        );

        $this->responseAsserter->assertUnauthorizedResponse($response);
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
            $this->fileSource->getId(),
            $filename
        );

        $this->responseAsserter->assertInvalidRequestJsonResponse($response, $expectedResponseData);
    }

    public function testRemoveFileSuccess(): void
    {
        $filename = TestConstants::FILENAME;
        $content = '- file content';
        $fileRelativePath = $this->sourceRelativePath . '/' . $filename;

        $this->fileSourceStorage->write($fileRelativePath, $content);

        $response = $this->applicationClient->makeRemoveFileRequest(
            $this->validToken,
            $this->fileSource->getId(),
            $filename
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertFalse($this->fileSourceStorage->fileExists($fileRelativePath));
    }

    public function testRemoveFileNotFound(): void
    {
        $response = $this->applicationClient->makeRemoveFileRequest(
            $this->validToken,
            $this->fileSource->getId(),
            TestConstants::FILENAME
        );

        $this->responseAsserter->assertSuccessfulResponseWithNoBody($response);
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
    public function testReadFileInvalidRequest(
        string $filename,
        array $expectedResponseData
    ): void {
        $response = $this->applicationClient->makeReadFileRequest(
            $this->validToken,
            $this->fileSource->getId(),
            $filename
        );

        $this->responseAsserter->assertInvalidRequestJsonResponse($response, $expectedResponseData);
    }

    public function testReadFileNotFound(): void
    {
        $response = $this->applicationClient->makeReadFileRequest(
            $this->validToken,
            $this->fileSource->getId(),
            TestConstants::FILENAME
        );

        $this->responseAsserter->assertNotFoundResponse($response);
    }

    public function testReadFileSuccess(): void
    {
        $filename = TestConstants::FILENAME;
        $content = '- file content';
        $fileRelativePath = $this->sourceRelativePath . '/' . $filename;

        $this->fileSourceStorage->write($fileRelativePath, $content);

        $response = $this->applicationClient->makeReadFileRequest(
            $this->validToken,
            $this->fileSource->getId(),
            $filename
        );

        $this->responseAsserter->assertReadSourceSuccessResponse($response, $content);
    }
}
