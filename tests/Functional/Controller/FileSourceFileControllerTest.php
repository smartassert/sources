<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\AbstractSource;
use App\Entity\FileSource;
use App\Services\FileStoreManager;
use App\Services\Source\Store;
use App\Tests\Model\Route;
use App\Tests\Services\AuthorizationRequestAsserter;
use App\Tests\Services\EntityRemover;
use App\Validator\YamlFilenameConstraint;
use Symfony\Component\HttpFoundation\JsonResponse;
use webignition\ObjectReflector\ObjectReflector;

class FileSourceFileControllerTest extends AbstractSourceControllerTest
{
    private const USER_ID = '01FVHKTM3V53JVCW1HPN1125NF';
    private const SOURCE_ID = '01FVHM0XGXGAD463JTW05CN2TF';
    private const SOURCE_RELATIVE_PATH = self::USER_ID . '/' . self::SOURCE_ID;
    private const EXPECTED_FILE_RELATIVE_PATH = self::SOURCE_RELATIVE_PATH . '/' . self::FILENAME;

    private const FILENAME = 'filename.yaml';
    private const CONTENT = '- list item';

    private const CREATE_DATA = [
        'content' => self::CONTENT,
    ];

    private const UPDATE_DATA = [
        'content' => self::CONTENT . ' updated',
    ];

    private AuthorizationRequestAsserter $authorizationRequestAsserter;
    private string $expectedFilePath;

    protected function setUp(): void
    {
        parent::setUp();

        $authorizationRequestAsserter = self::getContainer()->get(AuthorizationRequestAsserter::class);
        \assert($authorizationRequestAsserter instanceof AuthorizationRequestAsserter);
        $this->authorizationRequestAsserter = $authorizationRequestAsserter;

        $fileStoreBasePath = self::getContainer()->getParameter('file_store_base_path');
        \assert(is_string($fileStoreBasePath));
        $this->expectedFilePath = $fileStoreBasePath . '/' . self::EXPECTED_FILE_RELATIVE_PATH;

        $entityRemover = self::getContainer()->get(EntityRemover::class);
        if ($entityRemover instanceof EntityRemover) {
            $entityRemover->removeAll();
        }

        $source = new FileSource(self::USER_ID, 'file source label');
        ObjectReflector::setProperty(
            $source,
            AbstractSource::class,
            'id',
            self::SOURCE_ID
        );

        $store = self::getContainer()->get(Store::class);
        \assert($store instanceof Store);
        $store->add($source);
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
        $fileStoreManager = self::getContainer()->get(FileStoreManager::class);
        \assert($fileStoreManager instanceof FileStoreManager);
        $fileStoreManager->remove(self::SOURCE_RELATIVE_PATH);
        self::assertDirectoryDoesNotExist(self::SOURCE_RELATIVE_PATH);

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
        self::assertFileExists($this->expectedFilePath);
        self::assertSame(self::CREATE_DATA['content'], file_get_contents($this->expectedFilePath));
    }

    /**
     * @depends testAddFileSuccess
     */
    public function testUpdateAddedFileSuccess(): void
    {
        self::assertFileExists($this->expectedFilePath);
        self::assertSame(self::CREATE_DATA['content'], file_get_contents($this->expectedFilePath));

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
        self::assertFileExists($this->expectedFilePath);
        self::assertSame(self::UPDATE_DATA['content'], file_get_contents($this->expectedFilePath));
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

    /**
     * @depends testUpdateAddedFileSuccess
     */
    public function testRemoveFile(): void
    {
        self::assertFileExists($this->expectedFilePath);

        $this->setUserServiceAuthorizedResponse(self::USER_ID);

        $response = $this->applicationClient->makeAuthorizedRequest(
            'DELETE',
            new Route('file_source_file_remove', [
                'sourceId' => self::SOURCE_ID,
                'filename' => self::FILENAME,
            ])
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertFileDoesNotExist($this->expectedFilePath);
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
