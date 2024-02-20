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
            self::$users->get(self::USER_1_EMAIL)['id']
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
            'name missing .yaml extension, content non-empty' => [
                'filename' => 'non-yaml-filename',
                'content' => 'non-empty value',
                'expectedResponseData' => [
                    'class' => 'bad_request',
                    'parameter' => [
                        'name' => 'filename',
                        'value' => 'non-yaml-filename',
                        'requirements' => [
                            'data_type' => 'yaml_filename',
                        ],
                    ],
                    'type' => 'invalid',
                ],
            ],
            'name empty with .yaml extension, content non-empty' => [
                'filename' => '.yaml',
                'content' => 'non-empty value',
                'expectedResponseData' => [
                    'class' => 'bad_request',
                    'parameter' => [
                        'name' => 'filename',
                        'value' => '.yaml',
                        'requirements' => [
                            'data_type' => 'yaml_filename',
                        ],
                    ],
                    'type' => 'invalid',
                ],
            ],
            'name contains backslash characters, content non-empty' => [
                'filename' => 'one-two-\\-three.yaml',
                'content' => 'non-empty value',
                'expectedResponseData' => [
                    'class' => 'bad_request',
                    'parameter' => [
                        'name' => 'filename',
                        'value' => 'one-two-\\-three.yaml',
                        'requirements' => [
                            'data_type' => 'yaml_filename',
                        ],
                    ],
                    'type' => 'invalid',
                ],
            ],
            'name contains space characters, content non-empty' => [
                'filename' => 'one two three.yaml',
                'content' => 'non-empty value',
                'expectedResponseData' => [
                    'class' => 'bad_request',
                    'parameter' => [
                        'name' => 'filename',
                        'value' => 'one two three.yaml',
                        'requirements' => [
                            'data_type' => 'yaml_filename',
                        ],
                    ],
                    'type' => 'invalid',
                ],
            ],
            'name valid, content empty' => [
                'filename' => self::FILENAME,
                'content' => '',
                'expectedResponseData' => [
                    'class' => 'bad_request',
                    'parameter' => [
                        'name' => 'content',
                        'value' => '',
                        'requirements' => [
                            'data_type' => 'yaml',
                        ],
                    ],
                    'type' => 'empty',
                ],
            ],
            'name valid, content invalid yaml' => [
                'filename' => self::FILENAME,
                'content' => "- item\ncontent",
                'expectedResponseData' => [
                    'class' => 'bad_request',
                    'parameter' => [
                        'name' => 'content',
                        'value' => "- item\ncontent",
                        'requirements' => [
                            'data_type' => 'yaml',
                        ],
                    ],
                    'type' => 'invalid',
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
            'parameter' => [
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
     */
    public function testReadFileInvalidRequest(string $filename): void
    {
        $response = $this->applicationClient->makeReadFileRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $this->fileSource->getId(),
            $filename
        );

        self::assertSame(404, $response->getStatusCode());
    }

    /**
     * @return array<mixed>
     */
    public static function yamlFileInvalidRequestDataProvider(): array
    {
        return [
            'name missing .yaml extension' => [
                'filename' => 'non-yaml-filename',
                'expectedResponseData' => [
                    'class' => 'bad_request',
                    'parameter' => [
                        'name' => 'filename',
                        'value' => 'non-yaml-filename',
                        'requirements' => [
                            'data_type' => 'yaml_filename',
                        ],
                    ],
                    'type' => 'invalid',
                ],
            ],
            'name empty with .yaml extension' => [
                'filename' => '.yaml',
                'expectedResponseData' => [
                    'class' => 'bad_request',
                    'parameter' => [
                        'name' => 'filename',
                        'value' => '.yaml',
                        'requirements' => [
                            'data_type' => 'yaml_filename',
                        ],
                    ],
                    'type' => 'invalid',
                ],
            ],
            'name contains backslash characters' => [
                'filename' => 'one-two-\\-three.yaml',
                'expectedResponseData' => [
                    'class' => 'bad_request',
                    'parameter' => [
                        'name' => 'filename',
                        'value' => 'one-two-\\-three.yaml',
                        'requirements' => [
                            'data_type' => 'yaml_filename',
                        ],
                    ],
                    'type' => 'invalid',
                ],
            ],
            'name contains space characters' => [
                'filename' => 'one two three.yaml',
                'expectedResponseData' => [
                    'class' => 'bad_request',
                    'parameter' => [
                        'name' => 'filename',
                        'value' => 'one two three.yaml',
                        'requirements' => [
                            'data_type' => 'yaml_filename',
                        ],
                    ],
                    'type' => 'invalid',
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

    /**
     * @dataProvider yamlFileInvalidRequestDataProvider
     */
    public function testRemoveFileInvalidFilename(string $filename): void
    {
        $response = $this->applicationClient->makeRemoveFileRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $this->fileSource->getId(),
            $filename
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
