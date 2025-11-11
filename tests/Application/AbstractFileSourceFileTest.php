<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\FileSource;
use App\Repository\SourceRepository;
use App\Tests\Services\SourceOriginFactory;
use App\Tests\Services\StringFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Uid\Ulid;

abstract class AbstractFileSourceFileTest extends AbstractApplicationTest
{
    public const FILENAME = 'filename.yaml';

    private FileSource $fileSource;

    #[\Override]
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
     * @param array<mixed> $expectedResponseData
     */
    #[DataProvider('storeFileInvalidRequestDataProvider')]
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
     * @param array<mixed> $expectedResponseData
     */
    #[DataProvider('storeFileInvalidRequestDataProvider')]
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
    abstract public static function storeFileInvalidRequestDataProvider(): array;

    /**
     * @return array<mixed>
     */
    public static function storeFileInvalidRequestDefaultDataProvider(): array
    {
        return [
            'name empty, content non-empty' => [
                'filename' => '',
                'content' => 'non-empty value',
                'expectedResponseData' => [
                    'class' => 'bad_request',
                    'parameter' => [
                        'name' => 'filename',
                        'value' => '',
                        'requirements' => [
                            'data_type' => 'yaml_filename',
                        ],
                    ],
                    'type' => 'invalid',
                ],
            ],
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
        $initialContent = StringFactory::createRandom();

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

    public function testAddFileToDeletedSource(): void
    {
        $initialContent = md5((string) rand());

        $this->fileSource->setDeletedAt(new \DateTimeImmutable());

        $sourceRepository = self::getContainer()->get(SourceRepository::class);
        \assert($sourceRepository instanceof SourceRepository);
        $sourceRepository->save($this->fileSource);

        $response = $this->applicationClient->makeAddFileRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $this->fileSource->getId(),
            self::FILENAME,
            $initialContent
        );

        self::assertSame(405, $response->getStatusCode());

        $expectedResponseData = [
            'class' => 'modify_read_only',
            'entity' => [
                'id' => $this->fileSource->getId(),
                'type' => 'file-source',
            ],
        ];

        self::assertJsonStringEqualsJsonString(
            (string) json_encode($expectedResponseData),
            $response->getBody()->getContents(),
        );
    }

    #[DataProvider('yamlFileInvalidRequestDataProvider')]
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
    public static function yamlFileInvalidRequestDefaultDataProvider(): array
    {
        return [
            'name missing .yaml extension' => [
                'filename' => 'non-yaml-filename',
            ],
            'name empty with .yaml extension' => [
                'filename' => '.yaml',
            ],
            'name contains space characters' => [
                'filename' => 'one two three.yaml',
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

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('', $response->getHeaderLine('content-type'));
        self::assertSame('', $response->getBody()->getContents());
    }

    #[DataProvider('yamlFileInvalidRequestDataProvider')]
    public function testRemoveFileInvalidFilename(string $filename): void
    {
        $response = $this->applicationClient->makeRemoveFileRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $this->fileSource->getId(),
            $filename
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('', $response->getHeaderLine('content-type'));
        self::assertSame('', $response->getBody()->getContents());
    }

    /**
     * @return array<mixed>
     */
    abstract public static function yamlFileInvalidRequestDataProvider(): array;

    public function testReadFileNotFound(): void
    {
        $response = $this->applicationClient->makeReadFileRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $this->fileSource->getId(),
            self::FILENAME
        );

        self::assertSame(404, $response->getStatusCode());
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

        self::assertSame(200, $addResponse->getStatusCode());
        self::assertSame('', $addResponse->getHeaderLine('content-type'));
        self::assertSame('', $addResponse->getBody()->getContents());

        $initialReadResponse = $this->applicationClient->makeReadFileRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $this->fileSource->getId(),
            self::FILENAME
        );

        self::assertSame(200, $initialReadResponse->getStatusCode());
        self::assertSame('text/x-yaml; charset=utf-8', $initialReadResponse->getHeaderLine('content-type'));
        self::assertSame($initialContent, $initialReadResponse->getBody()->getContents());

        $updateResponse = $this->applicationClient->makeUpdateFileRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $this->fileSource->getId(),
            self::FILENAME,
            $updatedContent
        );

        self::assertSame(200, $updateResponse->getStatusCode());
        self::assertSame('', $updateResponse->getHeaderLine('content-type'));
        self::assertSame('', $updateResponse->getBody()->getContents());

        $updatedReadResponse = $this->applicationClient->makeReadFileRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $this->fileSource->getId(),
            self::FILENAME
        );

        self::assertSame(200, $updatedReadResponse->getStatusCode());
        self::assertSame('text/x-yaml; charset=utf-8', $updatedReadResponse->getHeaderLine('content-type'));
        self::assertSame($updatedContent, $updatedReadResponse->getBody()->getContents());

        $removeResponse = $this->applicationClient->makeRemoveFileRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $this->fileSource->getId(),
            self::FILENAME
        );

        self::assertSame(200, $removeResponse->getStatusCode());
        self::assertSame('', $removeResponse->getHeaderLine('content-type'));
        self::assertSame('', $removeResponse->getBody()->getContents());

        $notFoundReadResponse = $this->applicationClient->makeReadFileRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $this->fileSource->getId(),
            self::FILENAME
        );

        self::assertSame(404, $notFoundReadResponse->getStatusCode());
    }

    public function testCreateSourceNotFound(): void
    {
        $response = $this->applicationClient->makeAddFileRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            (string) new Ulid(),
            self::FILENAME,
            ''
        );

        self::assertSame(403, $response->getStatusCode());
    }

    public function testUpdateSourceNotFound(): void
    {
        $createResponse = $this->applicationClient->makeAddFileRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $this->fileSource->getId(),
            self::FILENAME,
            ''
        );

        self::assertSame(200, $createResponse->getStatusCode());

        $response = $this->applicationClient->makeUpdateFileRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            (string) new Ulid(),
            self::FILENAME,
            ''
        );

        self::assertSame(403, $response->getStatusCode());
    }

    public function testReadSourceNotFound(): void
    {
        $createResponse = $this->applicationClient->makeAddFileRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $this->fileSource->getId(),
            self::FILENAME,
            ''
        );

        self::assertSame(200, $createResponse->getStatusCode());

        $response = $this->applicationClient->makeReadFileRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            (string) new Ulid(),
            self::FILENAME
        );

        self::assertSame(403, $response->getStatusCode());
    }

    public function testRemoveSourceNotFound(): void
    {
        $createResponse = $this->applicationClient->makeAddFileRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $this->fileSource->getId(),
            self::FILENAME,
            ''
        );

        self::assertSame(200, $createResponse->getStatusCode());

        $response = $this->applicationClient->makeRemoveFileRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            (string) new Ulid(),
            self::FILENAME
        );

        self::assertSame(403, $response->getStatusCode());
    }
}
