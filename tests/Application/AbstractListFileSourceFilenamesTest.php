<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Request\FileSourceRequest;
use Symfony\Component\Uid\Ulid;

abstract class AbstractListFileSourceFilenamesTest extends AbstractApplicationTest
{
    /**
     * @var non-empty-string
     */
    private string $fileSourceId;

    protected function setUp(): void
    {
        parent::setUp();

        $label = 'file source label';

        $requestParameters = [
            FileSourceRequest::PARAMETER_LABEL => $label
        ];

        $createFileSourceResponse = $this->applicationClient->makeCreateFileSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(),
            $requestParameters
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
            self::$authenticationConfiguration->getValidApiToken(),
            $fileSourceId
        );

        self::assertSame(404, $response->getStatusCode());
    }

    /**
     * @dataProvider listSuccessDataProvider
     *
     * @param non-empty-string[] $filenamesToAdd
     * @param non-empty-string[] $expected
     */
    public function testListSuccess(array $filenamesToAdd, array $expected): void
    {
        foreach ($filenamesToAdd as $filename) {
            $this->applicationClient->makeAddFileRequest(
                self::$authenticationConfiguration->getValidApiToken(),
                $this->fileSourceId,
                $filename,
                md5((string) rand()),
            );
        }

        $response = $this->applicationClient->makeGetFileSourceFilenamesRequest(
            self::$authenticationConfiguration->getValidApiToken(),
            $this->fileSourceId
        );

        $this->responseAsserter->assertSuccessfulJsonResponse($response, $expected);
    }

    /**
     * @return array<mixed>
     */
    public function listSuccessDataProvider(): array
    {
        return [
            'no files' => [
                'filenamesToAdd' => [],
                'expected' => [],
            ],
            'single file without directory path' => [
                'filenamesToAdd' => [
                    'file.yaml'
                ],
                'expected' => [
                    'file.yaml'
                ],
            ],
            'single file with directory path' => [
                'filenamesToAdd' => [
                    'path/to/file.yaml'
                ],
                'expected' => [
                    'path/to/file.yaml'
                ],
            ],
            'multiple files' => [
                'filenamesToAdd' => [
                    'file1.yaml',
                    'file2.yaml',
                    'path/to/file3.yaml',
                    'another/path/to/file4.yaml',
                    'file5.yaml',
                ],
                'expected' => [
                    'another/path/to/file4.yaml',
                    'path/to/file3.yaml',
                    'file1.yaml',
                    'file2.yaml',
                    'file5.yaml',
                ],
            ],
        ];
    }
}
