<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\FileSource;
use App\Tests\Services\InvalidFilenameResponseDataFactory;
use App\Validator\YamlFilenameConstraint;

abstract class AbstractFileSourceFileTest extends AbstractApplicationTest
{
    public const FILENAME = 'filename.yaml';

    private FileSource $fileSource;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fileSource = new FileSource($this->authenticationConfiguration->authenticatedUserId, '');
        $this->store->add($this->fileSource);
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
                'filename' => self::FILENAME,
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
                'filename' => self::FILENAME,
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

    public function testRemoveFileNotFound(): void
    {
        $response = $this->applicationClient->makeRemoveFileRequest(
            $this->authenticationConfiguration->validToken,
            $this->fileSource->getId(),
            self::FILENAME
        );

        $this->responseAsserter->assertSuccessfulResponseWithNoBody($response);
    }

    public function testReadFileNotFound(): void
    {
        $response = $this->applicationClient->makeReadFileRequest(
            $this->authenticationConfiguration->validToken,
            $this->fileSource->getId(),
            self::FILENAME
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
            self::FILENAME,
            $initialContent
        );

        $this->responseAsserter->assertSuccessfulResponseWithNoBody($addResponse);

        $initialReadResponse = $this->applicationClient->makeReadFileRequest(
            $this->authenticationConfiguration->validToken,
            $this->fileSource->getId(),
            self::FILENAME
        );

        $this->responseAsserter->assertReadSourceSuccessResponse($initialReadResponse, $initialContent);

        $updateResponse = $this->applicationClient->makeAddFileRequest(
            $this->authenticationConfiguration->validToken,
            $this->fileSource->getId(),
            self::FILENAME,
            $updatedContent
        );

        $this->responseAsserter->assertSuccessfulResponseWithNoBody($updateResponse);

        $updatedReadResponse = $this->applicationClient->makeReadFileRequest(
            $this->authenticationConfiguration->validToken,
            $this->fileSource->getId(),
            self::FILENAME
        );

        $this->responseAsserter->assertReadSourceSuccessResponse($updatedReadResponse, $updatedContent);

        $removeResponse = $this->applicationClient->makeRemoveFileRequest(
            $this->authenticationConfiguration->validToken,
            $this->fileSource->getId(),
            self::FILENAME
        );

        $this->responseAsserter->assertSuccessfulResponseWithNoBody($removeResponse);

        $notFoundReadResponse = $this->applicationClient->makeReadFileRequest(
            $this->authenticationConfiguration->validToken,
            $this->fileSource->getId(),
            self::FILENAME
        );

        $this->responseAsserter->assertNotFoundResponse($notFoundReadResponse);
    }
}
