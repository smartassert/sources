<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Tests\Model\Route;
use App\Validator\YamlFilenameConstraint;
use Symfony\Component\HttpFoundation\JsonResponse;

class FileSourceFileControllerTest extends AbstractFileSourceFilesTest
{
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
        $this->setUserServiceAuthorizedResponse(self::USER_ID);

        $response = $this->applicationClient->makeAuthorizedRequest(
            'POST',
            new Route('file_source_file_add', [
                'sourceId' => self::SOURCE_ID,
                'filename' => $filename,
            ]),
            [
                'content' => $content,
            ]
        );

        self::assertSame(400, $response->getStatusCode());
        $this->authorizationRequestAsserter->assertAuthorizationRequestIsMade();
        self::assertInstanceOf(JsonResponse::class, $response);

        $responseData = json_decode((string) $response->getContent(), true);
        self::assertEquals($expectedResponseData, $responseData);
    }

    /**
     * @return array<mixed>
     */
    public function addFileInvalidRequestDataProvider(): array
    {
        $expectedInvalidFilenameResponseData = $this->createExpectedInvalidFilenameResponseData();

        return [
            'name empty with .yaml extension, content non-empty' => [
                'filename' => '.yaml',
                'content' => 'non-empty value',
                'expectedResponseData' => $expectedInvalidFilenameResponseData,
            ],
            'name contains backslash characters, content non-empty' => [
                'filename' => 'one two \\ three.yaml',
                'content' => 'non-empty value',
                'expectedResponseData' => $expectedInvalidFilenameResponseData,
            ],
            'name contains null byte characters, content non-empty' => [
                'filename' => 'one ' . chr(0) . ' two three' . chr(0) . '.yaml',
                'content' => 'non-empty value',
                'expectedResponseData' => $expectedInvalidFilenameResponseData,
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
        self::assertFalse($this->filesystemOperator->directoryExists(self::SOURCE_RELATIVE_PATH));

        $this->setUserServiceAuthorizedResponse(self::USER_ID);

        $response = $this->applicationClient->makeAuthorizedRequest(
            'POST',
            new Route('file_source_file_add', [
                'sourceId' => self::SOURCE_ID,
                'filename' => self::FILENAME,
            ]),
            self::CREATE_DATA
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertTrue($this->filesystemOperator->directoryExists(self::SOURCE_RELATIVE_PATH));
        self::assertTrue($this->filesystemOperator->fileExists(self::EXPECTED_FILE_RELATIVE_PATH));
        self::assertSame(
            self::CREATE_DATA['content'],
            $this->filesystemOperator->read(self::EXPECTED_FILE_RELATIVE_PATH)
        );
    }

    public function testUpdateAddedFileSuccess(): void
    {
        $this->filesystemOperator->write(self::EXPECTED_FILE_RELATIVE_PATH, self::CREATE_DATA['content']);

        $this->setUserServiceAuthorizedResponse(self::USER_ID);

        $response = $this->applicationClient->makeAuthorizedRequest(
            'POST',
            new Route('file_source_file_add', [
                'sourceId' => self::SOURCE_ID,
                'filename' => self::FILENAME,
            ]),
            self::UPDATE_DATA
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertTrue($this->filesystemOperator->directoryExists(self::SOURCE_RELATIVE_PATH));
        self::assertTrue($this->filesystemOperator->fileExists(self::EXPECTED_FILE_RELATIVE_PATH));
        self::assertSame(
            self::UPDATE_DATA['content'],
            $this->filesystemOperator->read(self::EXPECTED_FILE_RELATIVE_PATH)
        );
    }

    /**
     * @dataProvider removeFileInvalidRequestDataProvider
     *
     * @param array<mixed> $expectedResponseData
     */
    public function testRemoveFileInvalidRequest(
        string $filename,
        array $expectedResponseData
    ): void {
        $this->setUserServiceAuthorizedResponse(self::USER_ID);

        $response = $this->applicationClient->makeAuthorizedRequest(
            'DELETE',
            new Route('file_source_file_remove', [
                'sourceId' => self::SOURCE_ID,
                'filename' => $filename,
            ])
        );

        self::assertSame(400, $response->getStatusCode());
        $this->authorizationRequestAsserter->assertAuthorizationRequestIsMade();
        self::assertInstanceOf(JsonResponse::class, $response);

        $responseData = json_decode((string) $response->getContent(), true);
        self::assertEquals($expectedResponseData, $responseData);
    }

    /**
     * @return array<mixed>
     */
    public function removeFileInvalidRequestDataProvider(): array
    {
        $expectedInvalidFilenameResponseData = $this->createExpectedInvalidFilenameResponseData();

        return [
            'name empty with .yaml extension' => [
                'filename' => '.yaml',
                'expectedResponseData' => $expectedInvalidFilenameResponseData,
            ],
            'name contains backslash characters' => [
                'filename' => 'one two \\ three.yaml',
                'expectedResponseData' => $expectedInvalidFilenameResponseData,
            ],
            'name contains null byte characters' => [
                'filename' => 'one ' . chr(0) . ' two three' . chr(0) . '.yaml',
                'expectedResponseData' => $expectedInvalidFilenameResponseData,
            ],
        ];
    }

    public function testRemoveFileSuccess(): void
    {
        $this->filesystemOperator->write(self::EXPECTED_FILE_RELATIVE_PATH, self::CREATE_DATA['content']);

        $this->setUserServiceAuthorizedResponse(self::USER_ID);

        $response = $this->applicationClient->makeAuthorizedRequest(
            'DELETE',
            new Route('file_source_file_remove', [
                'sourceId' => self::SOURCE_ID,
                'filename' => self::FILENAME,
            ])
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertFalse($this->filesystemOperator->fileExists(self::EXPECTED_FILE_RELATIVE_PATH));
    }

    /**
     * @return array<mixed>
     */
    private function createExpectedInvalidFilenameResponseData(): array
    {
        return [
            'error' => [
                'type' => 'invalid_request',
                'payload' => [
                    'name' => [
                        'value' => '',
                        'message' => YamlFilenameConstraint::MESSAGE_NAME_INVALID,
                    ],
                ],
            ],
        ];
    }
}
