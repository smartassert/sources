<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Request\FileSourceRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Uid\Ulid;

abstract class AbstractListFileSourceFilenamesTest extends AbstractApplicationTest
{
    /**
     * @var non-empty-string
     */
    private string $fileSourceId;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $label = 'file source label';

        $createFileSourceResponse = $this->applicationClient->makeCreateFileSourceRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            [
                FileSourceRequest::PARAMETER_LABEL => $label
            ]
        );

        self::assertSame(200, $createFileSourceResponse->getStatusCode());
        self::assertSame('application/json', $createFileSourceResponse->getHeaderLine('content-type'));

        $createFileSourceResponseData = json_decode($createFileSourceResponse->getBody()->getContents(), true);
        \assert(is_array($createFileSourceResponseData));

        $fileSourceId = $createFileSourceResponseData['id'] ?? null;
        \assert(is_string($fileSourceId) && '' !== $fileSourceId);

        $this->fileSourceId = $fileSourceId;
    }

    public function testListFileSourceNotFound(): void
    {
        $fileSourceId = Ulid::generate();
        \assert('' !== $fileSourceId);

        $response = $this->applicationClient->makeGetFileSourceFilenamesRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $fileSourceId
        );

        $this->responseAsserter->assertForbiddenResponse($response);
    }

    /**
     * @param array<array{path: string, content: string}> $fileDataCollection
     * @param array<mixed>                                $expected
     */
    #[DataProvider('listSuccessDataProvider')]
    public function testListSuccess(array $fileDataCollection, array $expected): void
    {
        foreach ($fileDataCollection as $fileData) {
            $this->applicationClient->makeAddFileRequest(
                self::$apiTokens->get(self::USER_1_EMAIL),
                $this->fileSourceId,
                $fileData['path'],
                $fileData['content'],
            );
        }

        $response = $this->applicationClient->makeGetFileSourceFilenamesRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $this->fileSourceId
        );

        $this->responseAsserter->assertSuccessfulJsonResponse($response, $expected);
    }

    /**
     * @return array<mixed>
     */
    public static function listSuccessDataProvider(): array
    {
        return [
            'no files' => [
                'fileDataCollection' => [],
                'expected' => [],
            ],
            'single file without directory path' => [
                'fileDataCollection' => [
                    [
                        'path' => 'size1.yaml',
                        'content' => '.',
                    ],
                ],
                'expected' => [
                    [
                        'path' => 'size1.yaml',
                        'size' => 1,
                    ],
                ],
            ],
            'single file with directory path' => [
                'fileDataCollection' => [
                    [
                        'path' => 'path/to/file.yaml',
                        'content' => str_repeat('a', 10),
                    ],
                ],
                'expected' => [
                    [
                        'path' => 'path/to/file.yaml',
                        'size' => 10,
                    ],
                ],
            ],
            'multiple files' => [
                'fileDataCollection' => [
                    [
                        'path' => 'empty.yaml',
                        'content' => '',
                    ],
                    [
                        'path' => 'size1.yaml',
                        'content' => '.',
                    ],
                    [
                        'path' => 'size32.yaml',
                        'content' => str_repeat('.', 32),
                    ],
                    [
                        'path' => 'path/to/file3.yaml',
                        'content' => str_repeat('a', 10),
                    ],
                ],
                'expected' => [
                    [
                        'path' => 'empty.yaml',
                        'size' => 0,
                    ],
                    [
                        'path' => 'path/to/file3.yaml',
                        'size' => 10,
                    ],
                    [
                        'path' => 'size1.yaml',
                        'size' => 1,
                    ],
                    [
                        'path' => 'size32.yaml',
                        'size' => 32,
                    ],
                ],
            ],
        ];
    }
}
