<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\FileSource;
use App\Repository\SourceRepository;
use App\Tests\Services\SourceOriginFactory;

abstract class AbstractFileSourceFileTest extends AbstractApplicationTest
{
    public const FILENAME = 'filename.yaml';

    private FileSource $fileSource;

    protected function setUp(): void
    {
        parent::setUp();

        $fileSource = SourceOriginFactory::create(
            'file',
            self::$users->get(self::USER_1_EMAIL)->id
        );
        \assert($fileSource instanceof FileSource);
        $this->fileSource = $fileSource;

        $sourceRepository = self::getContainer()->get(SourceRepository::class);
        \assert($sourceRepository instanceof SourceRepository);
        $sourceRepository->save($this->fileSource);
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
            self::$apiTokens->get(self::USER_1_EMAIL),
            $this->fileSource->getId(),
            $filename,
            $content
        );

        self::assertSame(400, $response->getStatusCode());
        self::assertSame('application/json', $response->getHeaderLine('content-type'));

        self::assertJsonStringEqualsJsonString(
            (string) json_encode($expectedResponseData),
            $response->getBody()->getContents(),
        );
    }

    /**
     * @dataProvider addFileInvalidRequestDataProvider
     *
     * @param array<mixed> $expectedResponseData
     */
    public function testUpdateFileInvalidRequest(
        string $filename,
        string $content,
        array $expectedResponseData
    ): void {
        $response = $this->applicationClient->makeUpdateFileRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $this->fileSource->getId(),
            $filename,
            $content
        );

        self::assertSame(400, $response->getStatusCode());
        self::assertSame('application/json', $response->getHeaderLine('content-type'));

        self::assertJsonStringEqualsJsonString(
            (string) json_encode($expectedResponseData),
            $response->getBody()->getContents(),
        );
    }

    /**
     * @return array<mixed>
     */
    public static function addFileInvalidRequestDataProvider(): array
    {
        return [
            'name empty with .yaml extension, content non-empty' => [
                'filename' => '.yaml',
                'content' => 'non-empty value',
                'expectedResponseData' => [
                    'class' => 'bad_request',
                    'field' => [
                        'name' => 'filename',
                        'value' => '.yaml',
                    ],
                    'type' => 'invalid',
                    'requirements' => [
                        'data_type' => 'yaml_filename',
                    ],
                ],
            ],
            'name contains backslash characters, content non-empty' => [
                'filename' => 'one-two-\\-three.yaml',
                'content' => 'non-empty value',
                'expectedResponseData' => [
                    'class' => 'bad_request',
                    'field' => [
                        'name' => 'filename',
                        'value' => 'one-two-\\-three.yaml',
                    ],
                    'type' => 'invalid',
                    'requirements' => [
                        'data_type' => 'yaml_filename',
                    ],
                ],
            ],
            'name contains space characters, content non-empty' => [
                'filename' => 'one two three.yaml',
                'content' => 'non-empty value',
                'expectedResponseData' => [
                    'class' => 'bad_request',
                    'field' => [
                        'name' => 'filename',
                        'value' => 'one two three.yaml',
                    ],
                    'type' => 'invalid',
                    'requirements' => [
                        'data_type' => 'yaml_filename',
                    ],
                ],
            ],
            'name valid, content empty' => [
                'filename' => self::FILENAME,
                'content' => '',
                'expectedResponseData' => [
                    'class' => 'bad_request',
                    'field' => [
                        'name' => 'content',
                        'value' => '',
                    ],
                    'type' => 'empty',
                    'requirements' => [
                        'data_type' => 'yaml',
                    ],
                ],
            ],
            'name valid, content invalid yaml' => [
                'filename' => self::FILENAME,
                'content' => "- item\ncontent",
                'expectedResponseData' => [
                    'class' => 'bad_request',
                    'field' => [
                        'name' => 'content',
                        'value' => "- item\ncontent",
                    ],
                    'type' => 'invalid',
                    'requirements' => [
                        'data_type' => 'yaml',
                    ],
                ],
            ],
        ];
    }

    public function testAddFileDuplicateFilename(): void
    {
        $initialContent = md5((string) rand());

        $this->applicationClient->makeAddFileRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $this->fileSource->getId(),
            self::FILENAME,
            $initialContent
        );

        $failedAddResponse = $this->applicationClient->makeAddFileRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $this->fileSource->getId(),
            self::FILENAME,
            $initialContent
        );

        $expectedResponseData = [
            'class' => 'duplicate',
            'field' => [
                'name' => 'filename',
                'value' => self::FILENAME,
            ],
        ];

        self::assertJsonStringEqualsJsonString(
            (string) json_encode($expectedResponseData),
            $failedAddResponse->getBody()->getContents(),
        );
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
            self::$apiTokens->get(self::USER_1_EMAIL),
            $this->fileSource->getId(),
            $filename
        );

        self::assertSame(400, $response->getStatusCode());
        self::assertSame('application/json', $response->getHeaderLine('content-type'));

        self::assertJsonStringEqualsJsonString(
            (string) json_encode($expectedResponseData),
            $response->getBody()->getContents(),
        );
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
            self::$apiTokens->get(self::USER_1_EMAIL),
            $this->fileSource->getId(),
            $filename
        );

        self::assertSame(400, $response->getStatusCode());
        self::assertSame('application/json', $response->getHeaderLine('content-type'));

        self::assertJsonStringEqualsJsonString(
            (string) json_encode($expectedResponseData),
            $response->getBody()->getContents(),
        );
    }

    /**
     * @return array<mixed>
     */
    public static function yamlFileInvalidRequestDataProvider(): array
    {
        return [
            'name empty with .yaml extension' => [
                'filename' => '.yaml',
                'expectedResponseData' => [
                    'class' => 'bad_request',
                    'field' => [
                        'name' => 'filename',
                        'value' => '.yaml',
                    ],
                    'type' => 'invalid',
                    'requirements' => [
                        'data_type' => 'yaml_filename',
                    ],
                ],
            ],
            'name contains backslash characters' => [
                'filename' => 'one-two-\\-three.yaml',
                'expectedResponseData' => [
                    'class' => 'bad_request',
                    'field' => [
                        'name' => 'filename',
                        'value' => 'one-two-\\-three.yaml',
                    ],
                    'type' => 'invalid',
                    'requirements' => [
                        'data_type' => 'yaml_filename',
                    ],
                ],
            ],
            'name contains space characters' => [
                'filename' => 'one two three.yaml',
                'expectedResponseData' => [
                    'class' => 'bad_request',
                    'field' => [
                        'name' => 'filename',
                        'value' => 'one two three.yaml',
                    ],
                    'type' => 'invalid',
                    'requirements' => [
                        'data_type' => 'yaml_filename',
                    ],
                ],
            ],
        ];
    }

    public function testRemoveFileNotFound(): void
    {
        $response = $this->applicationClient->makeRemoveFileRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $this->fileSource->getId(),
            self::FILENAME
        );

        $this->responseAsserter->assertSuccessfulResponseWithNoBody($response);
    }

    public function testReadFileNotFound(): void
    {
        $response = $this->applicationClient->makeReadFileRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
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
            self::$apiTokens->get(self::USER_1_EMAIL),
            $this->fileSource->getId(),
            self::FILENAME,
            $initialContent
        );

        $this->responseAsserter->assertSuccessfulResponseWithNoBody($addResponse);

        $initialReadResponse = $this->applicationClient->makeReadFileRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $this->fileSource->getId(),
            self::FILENAME
        );

        $this->responseAsserter->assertReadSourceSuccessResponse($initialReadResponse, $initialContent);

        $updateResponse = $this->applicationClient->makeUpdateFileRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $this->fileSource->getId(),
            self::FILENAME,
            $updatedContent
        );

        $this->responseAsserter->assertSuccessfulResponseWithNoBody($updateResponse);

        $updatedReadResponse = $this->applicationClient->makeReadFileRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $this->fileSource->getId(),
            self::FILENAME
        );

        $this->responseAsserter->assertReadSourceSuccessResponse($updatedReadResponse, $updatedContent);

        $removeResponse = $this->applicationClient->makeRemoveFileRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $this->fileSource->getId(),
            self::FILENAME
        );

        $this->responseAsserter->assertSuccessfulResponseWithNoBody($removeResponse);

        $notFoundReadResponse = $this->applicationClient->makeReadFileRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $this->fileSource->getId(),
            self::FILENAME
        );

        $this->responseAsserter->assertNotFoundResponse($notFoundReadResponse);
    }
}
