<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Enum\Source\Type;
use App\Request\FileSourceRequest;
use App\Request\OriginSourceRequest;
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
            OriginSourceRequest::PARAMETER_TYPE => Type::FILE->value,
            FileSourceRequest::PARAMETER_LABEL => $label
        ];

        $createFileSourceResponse = $this->applicationClient->makeCreateSourceRequest(
            self::$authenticationConfiguration->getApiToken(self::USER_1_EMAIL),
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
            self::$authenticationConfiguration->getApiToken(self::USER_1_EMAIL),
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
                self::$authenticationConfiguration->getApiToken(self::USER_1_EMAIL),
                $this->fileSourceId,
                $filename,
                md5((string) rand()),
            );
        }

        $response = $this->applicationClient->makeGetFileSourceFilenamesRequest(
            self::$authenticationConfiguration->getApiToken(self::USER_1_EMAIL),
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
