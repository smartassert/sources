<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\FileSource;
use App\Model\EntityId;
use App\Services\Source\Store;
use App\Tests\Model\UserId;
use App\Tests\Services\InvalidFilenameResponseDataFactory;
use App\Validator\YamlFilenameConstraint;
use League\Flysystem\FilesystemOperator;

class FileSourceFileControllerTest extends AbstractSourceControllerTest
{
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
        $response = $this->application->makeAddFileRequest(
            $this->invalidToken,
            $this->fileSource->getId(),
            'filename.yaml',
            '- content'
        );

        $this->responseAsserter->assertUnauthorizedResponse($response);
        $this->requestAsserter->assertAuthorizationRequestIsMade($this->invalidToken);
    }

    public function testAddFileInvalidSourceUser(): void
    {
        $source = new FileSource(UserId::create(), '');
        $this->store->add($source);

        $response = $this->application->makeAddFileRequest(
            $this->validToken,
            $source->getId(),
            'filename.yaml',
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
        $response = $this->application->makeAddFileRequest(
            $this->validToken,
            $this->fileSource->getId(),
            $filename,
            $content
        );

        $this->responseAsserter->assertInvalidRequestJsonResponse($response, $expectedResponseData);
        $this->requestAsserter->assertAuthorizationRequestIsMade();
    }

    /**
     * @return array<mixed>
     */
    public function addFileInvalidRequestDataProvider(): array
    {
        return [
            'name empty with .yaml extension, content non-empty' => [
                'filename' => '.yaml',
                'content' => 'non-empty value',
                'expectedResponseData' => InvalidFilenameResponseDataFactory::createForMessage(
                    YamlFilenameConstraint::MESSAGE_NAME_EMPTY
                ),
            ],
            'name contains backslash characters, content non-empty' => [
                'filename' => 'one-two-\\-three.yaml',
                'content' => 'non-empty value',
                'expectedResponseData' => InvalidFilenameResponseDataFactory::createForMessage(
                    YamlFilenameConstraint::MESSAGE_FILENAME_INVALID
                ),
            ],
            'name contains space characters, content non-empty' => [
                'filename' => 'one two three.yaml',
                'content' => 'non-empty value',
                'expectedResponseData' => InvalidFilenameResponseDataFactory::createForMessage(
                    YamlFilenameConstraint::MESSAGE_FILENAME_INVALID
                ),
            ],
            'name valid, content empty' => [
                'filename' => 'filename.yaml',
                'content' => '',
                'expectedResponseData' => [
                    'error' => [
                        'type' => 'invalid_request',
                        'payload' => [
                            'content' => [
                                'value' => '',
                                'message' => 'File content must not be empty.',
                            ],
                        ],
                    ],
                ],
            ],
            'name valid, content invalid yaml' => [
                'filename' => 'filename.yaml',
                'content' => "- item\ncontent",
                'expectedResponseData' => [
                    'error' => [
                        'type' => 'invalid_request',
                        'payload' => [
                            'content' => [
                                'value' => '',
                                'message' => 'Content must be valid YAML: Unable to parse at line 2 (near "content").',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function testAddFileSuccess(): void
    {
        $filename = 'filename.yaml';
        $content = '- file content';
        $fileRelativePath = $this->sourceRelativePath . '/' . $filename;

        self::assertFalse($this->fileSourceStorage->directoryExists($this->sourceRelativePath));
        self::assertFalse($this->fileSourceStorage->fileExists($fileRelativePath));

        $response = $this->application->makeAddFileRequest(
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
        $filename = 'filename.yaml';
        $initialContent = '- initial content';
        $updatedContent = '- updated content';
        $fileRelativePath = $this->sourceRelativePath . '/' . $filename;

        $this->fileSourceStorage->write($fileRelativePath, $initialContent);

        $response = $this->application->makeAddFileRequest(
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
        $response = $this->application->makeRemoveFileRequest(
            $this->invalidToken,
            $this->fileSource->getId(),
            'filename.yaml'
        );

        $this->responseAsserter->assertUnauthorizedResponse($response);
        $this->requestAsserter->assertAuthorizationRequestIsMade($this->invalidToken);
    }

    public function testRemoveFileInvalidSourceUser(): void
    {
        $source = new FileSource(UserId::create(), '');
        $this->store->add($source);

        $response = $this->application->makeRemoveFileRequest($this->validToken, $source->getId(), 'filename.yaml');

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
        $response = $this->application->makeRemoveFileRequest($this->validToken, $this->fileSource->getId(), $filename);

        $this->responseAsserter->assertInvalidRequestJsonResponse($response, $expectedResponseData);
        $this->requestAsserter->assertAuthorizationRequestIsMade();
    }

    public function testRemoveFileSuccess(): void
    {
        $filename = 'filename.yaml';
        $content = '- file content';
        $fileRelativePath = $this->sourceRelativePath . '/' . $filename;

        $this->fileSourceStorage->write($fileRelativePath, $content);

        $response = $this->application->makeRemoveFileRequest($this->validToken, $this->fileSource->getId(), $filename);

        self::assertSame(200, $response->getStatusCode());
        self::assertFalse($this->fileSourceStorage->fileExists($fileRelativePath));
    }

    public function testRemoveFileNotFound(): void
    {
        $response = $this->application->makeRemoveFileRequest(
            $this->validToken,
            $this->fileSource->getId(),
            'filename.yaml'
        );

        $this->responseAsserter->assertSuccessfulResponseWithNoBody($response);
    }

    public function testReadFileUnauthorizedUser(): void
    {
        $response = $this->application->makeRemoveFileRequest($this->invalidToken, EntityId::create(), 'filename.yaml');

        $this->responseAsserter->assertUnauthorizedResponse($response);
        $this->requestAsserter->assertAuthorizationRequestIsMade($this->invalidToken);
    }

    public function testReadFileInvalidSourceUser(): void
    {
        $source = new FileSource(UserId::create(), '');
        $this->store->add($source);

        $response = $this->application->makeRemoveFileRequest(
            $this->validToken,
            $source->getId(),
            'filename.yaml'
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
        $response = $this->application->makeReadFileRequest(
            $this->validToken,
            $this->fileSource->getId(),
            $filename
        );

        $this->responseAsserter->assertInvalidRequestJsonResponse($response, $expectedResponseData);
    }

    public function testReadFileNotFound(): void
    {
        $response = $this->application->makeReadFileRequest(
            $this->validToken,
            $this->fileSource->getId(),
            'filename.yaml'
        );

        $this->responseAsserter->assertNotFoundResponse($response);
    }

    public function testReadFileSuccess(): void
    {
        $filename = 'filename.yaml';
        $content = '- file content';
        $fileRelativePath = $this->sourceRelativePath . '/' . $filename;

        $this->fileSourceStorage->write($fileRelativePath, $content);

        $response = $this->application->makeReadFileRequest(
            $this->validToken,
            $this->fileSource->getId(),
            $filename
        );

        $this->responseAsserter->assertReadSourceSuccessResponse($response, $content);
    }

    /**
     * @return array<mixed>
     */
    public function yamlFileInvalidRequestDataProvider(): array
    {
        return [
            'name empty with .yaml extension' => [
                'filename' => '.yaml',
                'expectedResponseData' => InvalidFilenameResponseDataFactory::createForMessage(
                    YamlFilenameConstraint::MESSAGE_NAME_EMPTY
                ),
            ],
            'name contains backslash characters' => [
                'filename' => 'one-two-\\-three.yaml',
                'expectedResponseData' => InvalidFilenameResponseDataFactory::createForMessage(
                    YamlFilenameConstraint::MESSAGE_FILENAME_INVALID
                ),
            ],
            'name contains space characters' => [
                'filename' => 'one two three.yaml',
                'expectedResponseData' => InvalidFilenameResponseDataFactory::createForMessage(
                    YamlFilenameConstraint::MESSAGE_FILENAME_INVALID
                ),
            ],
        ];
    }
}
