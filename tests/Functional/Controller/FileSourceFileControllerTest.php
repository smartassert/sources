<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\FileSource;
use App\Services\Source\Store;
use App\Tests\Model\UserId;
use App\Tests\Services\AuthorizationRequestAsserter;
use App\Validator\YamlFilenameConstraint;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\HttpFoundation\JsonResponse;

class FileSourceFileControllerTest extends AbstractSourceControllerTest
{
    private AuthorizationRequestAsserter $authorizationRequestAsserter;
    private FilesystemOperator $fileSourceStorage;

    private FileSource $fileSource;
    private Store $store;
    private string $sourceRelativePath;

    protected function setUp(): void
    {
        parent::setUp();

        $authorizationRequestAsserter = self::getContainer()->get(AuthorizationRequestAsserter::class);
        \assert($authorizationRequestAsserter instanceof AuthorizationRequestAsserter);
        $this->authorizationRequestAsserter = $authorizationRequestAsserter;

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
        $url = $this->generateUrl('file_source_file_add', [
            'sourceId' => $this->fileSource->getId(),
            'filename' => 'filename.yaml',
        ]);

        $response = $this->applicationClient->makeUnauthorizedRequest('POST', $url);

        self::assertSame(401, $response->getStatusCode());
        $this->authorizationRequestAsserter->assertAuthorizationRequestIsMade(
            $this->authenticationConfiguration->invalidToken
        );
    }

    public function testAddFileInvalidSourceUser(): void
    {
        $source = new FileSource(UserId::create(), '');
        $this->store->add($source);

        $url = $this->generateUrl('file_source_file_add', [
            'sourceId' => $source->getId(),
            'filename' => 'filename.yaml',
        ]);

        $response = $this->applicationClient->makeAuthorizedRequest('POST', $url, [], '- content');

        self::assertSame(403, $response->getStatusCode());
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
        $url = $this->generateUrl('file_source_file_add', [
            'sourceId' => $this->fileSource->getId(),
            'filename' => $filename,
        ]);

        $response = $this->applicationClient->makeAuthorizedRequest('POST', $url, [], $content);

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
        return [
            'name empty with .yaml extension, content non-empty' => [
                'filename' => '.yaml',
                'content' => 'non-empty value',
                'expectedResponseData' => $this->createExpectedInvalidFilenameResponseData(
                    YamlFilenameConstraint::MESSAGE_NAME_EMPTY
                ),
            ],
            'name contains backslash characters, content non-empty' => [
                'filename' => 'one-two-\\-three.yaml',
                'content' => 'non-empty value',
                'expectedResponseData' => $this->createExpectedInvalidFilenameResponseData(
                    YamlFilenameConstraint::MESSAGE_FILENAME_INVALID
                ),
            ],
            'name contains null byte characters, content non-empty' => [
                'filename' => 'one-' . chr(0) . '-two-three' . chr(0) . '.yaml',
                'content' => 'non-empty value',
                'expectedResponseData' => $this->createExpectedInvalidFilenameResponseData(
                    YamlFilenameConstraint::MESSAGE_FILENAME_INVALID
                ),
            ],
            'name contains space characters, content non-empty' => [
                'filename' => 'one two three.yaml',
                'content' => 'non-empty value',
                'expectedResponseData' => $this->createExpectedInvalidFilenameResponseData(
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

        $url = $this->generateUrl('file_source_file_add', [
            'sourceId' => $this->fileSource->getId(),
            'filename' => $filename,
        ]);

        $response = $this->applicationClient->makeAuthorizedRequest(
            'POST',
            $url,
            [],
            $content,
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

        $url = $this->generateUrl('file_source_file_add', [
            'sourceId' => $this->fileSource->getId(),
            'filename' => $filename,
        ]);

        $response = $this->applicationClient->makeAuthorizedRequest('POST', $url, [], $updatedContent);

        self::assertSame(200, $response->getStatusCode());
        self::assertTrue($this->fileSourceStorage->directoryExists($this->sourceRelativePath));
        self::assertTrue($this->fileSourceStorage->fileExists($fileRelativePath));
        self::assertSame($updatedContent, $this->fileSourceStorage->read($fileRelativePath));
    }

    public function testRemoveFileUnauthorizedUser(): void
    {
        $url = $this->generateUrl('file_source_file_remove', [
            'sourceId' => $this->fileSource->getId(),
            'filename' => 'filename.yaml',
        ]);

        $response = $this->applicationClient->makeUnauthorizedRequest('DELETE', $url);

        self::assertSame(401, $response->getStatusCode());
        $this->authorizationRequestAsserter->assertAuthorizationRequestIsMade(
            $this->authenticationConfiguration->invalidToken
        );
    }

    public function testRemoveFileInvalidSourceUser(): void
    {
        $source = new FileSource(UserId::create(), '');
        $this->store->add($source);

        $url = $this->generateUrl('file_source_file_remove', [
            'sourceId' => $source->getId(),
            'filename' => 'filename.yaml',
        ]);

        $response = $this->applicationClient->makeAuthorizedRequest('DELETE', $url);

        self::assertSame(403, $response->getStatusCode());
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
        $url = $this->generateUrl('file_source_file_remove', [
            'sourceId' => $this->fileSource->getId(),
            'filename' => $filename,
        ]);

        $response = $this->applicationClient->makeAuthorizedRequest('DELETE', $url);

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
        return [
            'name empty with .yaml extension' => [
                'filename' => '.yaml',
                'expectedResponseData' => $this->createExpectedInvalidFilenameResponseData(
                    YamlFilenameConstraint::MESSAGE_NAME_EMPTY
                ),
            ],
            'name contains backslash characters' => [
                'filename' => 'one-two-\\-three.yaml',
                'expectedResponseData' => $this->createExpectedInvalidFilenameResponseData(
                    YamlFilenameConstraint::MESSAGE_FILENAME_INVALID
                ),
            ],
            'name contains null byte characters' => [
                'filename' => 'one-' . chr(0) . '-two-three' . chr(0) . '.yaml',
                'expectedResponseData' => $this->createExpectedInvalidFilenameResponseData(
                    YamlFilenameConstraint::MESSAGE_FILENAME_INVALID
                ),
            ],
            'name contains space characters' => [
                'filename' => 'one two three.yaml',
                'expectedResponseData' => $this->createExpectedInvalidFilenameResponseData(
                    YamlFilenameConstraint::MESSAGE_FILENAME_INVALID
                ),
            ],
        ];
    }

    public function testRemoveFileSuccess(): void
    {
        $filename = 'filename.yaml';
        $content = '- file content';
        $fileRelativePath = $this->sourceRelativePath . '/' . $filename;

        $this->fileSourceStorage->write($fileRelativePath, $content);

        $url = $this->generateUrl('file_source_file_remove', [
            'sourceId' => $this->fileSource->getId(),
            'filename' => $filename,
        ]);

        $response = $this->applicationClient->makeAuthorizedRequest('DELETE', $url);

        self::assertSame(200, $response->getStatusCode());
        self::assertFalse($this->fileSourceStorage->fileExists($fileRelativePath));
    }

    public function testReadFileUnauthorizedUser(): void
    {
        $url = $this->generateUrl('file_source_file_read', [
            'sourceId' => $this->fileSource->getId(),
            'filename' => 'filename.yaml',
        ]);

        $response = $this->applicationClient->makeUnauthorizedRequest('GET', $url);

        self::assertSame(401, $response->getStatusCode());
        $this->authorizationRequestAsserter->assertAuthorizationRequestIsMade(
            $this->authenticationConfiguration->invalidToken
        );
    }

    public function testReadFileInvalidSourceUser(): void
    {
        $source = new FileSource(UserId::create(), '');
        $this->store->add($source);

        $url = $this->generateUrl('file_source_file_read', [
            'sourceId' => $source->getId(),
            'filename' => 'filename.yaml',
        ]);

        $response = $this->applicationClient->makeAuthorizedRequest('GET', $url);

        self::assertSame(403, $response->getStatusCode());
    }

    /**
     * @dataProvider readFileInvalidRequestDataProvider
     *
     * @param array<mixed> $expectedResponseData
     */
    public function testReadFileInvalidRequest(
        string $filename,
        array $expectedResponseData
    ): void {
        $url = $this->generateUrl('file_source_file_read', [
            'sourceId' => $this->fileSource->getId(),
            'filename' => $filename,
        ]);

        $response = $this->applicationClient->makeAuthorizedRequest('GET', $url);

        self::assertSame(400, $response->getStatusCode());
        $this->authorizationRequestAsserter->assertAuthorizationRequestIsMade();
        self::assertInstanceOf(JsonResponse::class, $response);

        $responseData = json_decode((string) $response->getContent(), true);
        self::assertEquals($expectedResponseData, $responseData);
    }

    /**
     * @return array<mixed>
     */
    public function readFileInvalidRequestDataProvider(): array
    {
        return [
            'name empty with .yaml extension' => [
                'filename' => '.yaml',
                'expectedResponseData' => $this->createExpectedInvalidFilenameResponseData(
                    YamlFilenameConstraint::MESSAGE_NAME_EMPTY
                ),
            ],
            'name contains backslash characters' => [
                'filename' => 'one-two-\\-three.yaml',
                'expectedResponseData' => $this->createExpectedInvalidFilenameResponseData(
                    YamlFilenameConstraint::MESSAGE_FILENAME_INVALID
                ),
            ],
            'name contains null byte characters' => [
                'filename' => 'one-' . chr(0) . '-two-three' . chr(0) . '.yaml',
                'expectedResponseData' => $this->createExpectedInvalidFilenameResponseData(
                    YamlFilenameConstraint::MESSAGE_FILENAME_INVALID
                ),
            ],
            'name contains space characters' => [
                'filename' => 'one two three.yaml',
                'expectedResponseData' => $this->createExpectedInvalidFilenameResponseData(
                    YamlFilenameConstraint::MESSAGE_FILENAME_INVALID
                ),
            ],
        ];
    }

    public function testReadFileSuccess(): void
    {
        $filename = 'filename.yaml';
        $content = '- file content';
        $fileRelativePath = $this->sourceRelativePath . '/' . $filename;

        $this->fileSourceStorage->write($fileRelativePath, $content);

        $url = $this->generateUrl('file_source_file_read', [
            'sourceId' => $this->fileSource->getId(),
            'filename' => $filename,
        ]);

        $response = $this->applicationClient->makeAuthorizedRequest('GET', $url);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('text/x-yaml; charset=utf-8', $response->headers->get('content-type'));
        self::assertSame($content, $response->getContent());
    }

    /**
     * @return array<mixed>
     */
    private function createExpectedInvalidFilenameResponseData(string $message): array
    {
        return [
            'error' => [
                'type' => 'invalid_request',
                'payload' => [
                    'name' => [
                        'value' => '',
                        'message' => $message,
                    ],
                ],
            ],
        ];
    }
}
