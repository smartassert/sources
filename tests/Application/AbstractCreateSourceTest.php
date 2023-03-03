<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\SourceInterface;
use App\Enum\Source\Type;
use App\Repository\SourceRepository;
use App\Request\FileSourceRequest;
use App\Request\GitSourceRequest;
use App\Request\OriginSourceRequest;
use App\Tests\DataProvider\CreateUpdateFileSourceDataProviderTrait;
use App\Tests\DataProvider\CreateUpdateGitSourceDataProviderTrait;

abstract class AbstractCreateSourceTest extends AbstractApplicationTest
{
    use CreateUpdateFileSourceDataProviderTrait;
    use CreateUpdateGitSourceDataProviderTrait;

    public function testCreateInvalidSourceType(): void
    {
        $response = $this->applicationClient->makeCreateSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            [
                OriginSourceRequest::PARAMETER_TYPE => 'invalid source type',
            ]
        );

        $this->responseAsserter->assertInvalidRequestJsonResponse(
            $response,
            [
                'error' => [
                    'type' => 'invalid_request',
                    'payload' => [
                        'name' => 'type',
                        'value' => 'invalid source type',
                        'message' => 'Source type must be one of: file, git.',
                    ],
                ],
            ]
        );
    }

    /**
     * @dataProvider createUpdateFileSourceInvalidRequestDataProvider
     * @dataProvider createUpdateGitSourceInvalidRequestDataProvider
     *
     * @param array<string, string> $requestParameters
     * @param array<string, string> $expectedResponseData
     */
    public function testCreateInvalidSourceRequest(array $requestParameters, array $expectedResponseData): void
    {
        $response = $this->applicationClient->makeCreateSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            $requestParameters
        );

        $this->responseAsserter->assertInvalidRequestJsonResponse($response, $expectedResponseData);
    }

    /**
     * @dataProvider createFileSourceSuccessDataProvider
     * @dataProvider createGitSourceSuccessDataProvider
     *
     * @param array<string, string> $requestParameters
     * @param array<mixed>          $expected
     */
    public function testCreateSuccess(array $requestParameters, array $expected): void
    {
        $response = $this->applicationClient->makeCreateSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            $requestParameters
        );

        $sources = [];
        $sourceRepository = self::getContainer()->get(SourceRepository::class);
        if ($sourceRepository instanceof SourceRepository) {
            $sources = $sourceRepository->findAll();
        }

        self::assertIsArray($sources);
        self::assertCount(1, $sources);

        $source = $sources[0];
        self::assertInstanceOf(SourceInterface::class, $source);

        $expected['id'] = $source->getId();
        $expected['user_id'] = self::$authenticationConfiguration->getUser(self::USER_1_EMAIL)->id;

        $this->responseAsserter->assertSuccessfulJsonResponse($response, $expected);
    }

    /**
     * @return array<mixed>
     */
    public function createFileSourceSuccessDataProvider(): array
    {
        $label = md5((string) rand());

        return [
            'file source' => [
                'requestParameters' => [
                    OriginSourceRequest::PARAMETER_TYPE => Type::FILE->value,
                    FileSourceRequest::PARAMETER_LABEL => $label
                ],
                'expected' => [
                    'type' => Type::FILE->value,
                    'label' => $label,
                ],
            ],
        ];
    }

    /**
     * @return array<mixed>
     */
    public function createGitSourceSuccessDataProvider(): array
    {
        $label = md5((string) rand());
        $hostUrl = 'https://example.com/' . md5((string) rand()) . '.git';
        $path = '/' . md5((string) rand());
        $credentials = md5((string) rand());

        return [
            'git source, credentials missing' => [
                'requestParameters' => [
                    OriginSourceRequest::PARAMETER_TYPE => Type::GIT->value,
                    GitSourceRequest::PARAMETER_LABEL => $label,
                    GitSourceRequest::PARAMETER_HOST_URL => $hostUrl,
                    GitSourceRequest::PARAMETER_PATH => $path
                ],
                'expected' => [
                    'type' => Type::GIT->value,
                    'label' => $label,
                    'host_url' => $hostUrl,
                    'path' => $path,
                    'has_credentials' => false,
                ],
            ],
            'git source, credentials present' => [
                'requestParameters' => [
                    OriginSourceRequest::PARAMETER_TYPE => Type::GIT->value,
                    GitSourceRequest::PARAMETER_LABEL => $label,
                    GitSourceRequest::PARAMETER_HOST_URL => $hostUrl,
                    GitSourceRequest::PARAMETER_PATH => $path,
                    GitSourceRequest::PARAMETER_CREDENTIALS => $credentials,
                ],
                'expected' => [
                    'type' => Type::GIT->value,
                    'label' => $label,
                    'host_url' => $hostUrl,
                    'path' => $path,
                    'has_credentials' => true,
                ],
            ],
        ];
    }

    /**
     * @dataProvider createFileSourceSuccessDataProvider
     * @dataProvider createGitSourceSuccessDataProvider
     *
     * @param array<string, string> $requestParameters
     */
    public function testCreateIsIdempotent(array $requestParameters): void
    {
        $firstResponse = $this->applicationClient->makeCreateSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            $requestParameters
        );

        self::assertSame(200, $firstResponse->getStatusCode());

        $secondResponse = $this->applicationClient->makeCreateSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            $requestParameters
        );

        self::assertSame(200, $secondResponse->getStatusCode());
        self::assertSame($firstResponse->getBody()->getContents(), $secondResponse->getBody()->getContents());
    }

    /**
     * @dataProvider createFileSourceSuccessDataProvider
     * @dataProvider createGitSourceSuccessDataProvider
     *
     * @param array<string, string> $requestParameters
     */
    public function testCreateWithLabelOfDeletedSource(array $requestParameters): void
    {
        $firstCreateResponse = $this->applicationClient->makeCreateSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            $requestParameters
        );

        self::assertSame(200, $firstCreateResponse->getStatusCode());

        $firstCreateResponseData = json_decode($firstCreateResponse->getBody()->getContents(), true);
        \assert(is_array($firstCreateResponseData));
        $sourceId = $firstCreateResponseData['id'] ?? null;
        \assert(is_string($sourceId));

        $deleteResponse = $this->applicationClient->makeDeleteSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            $sourceId
        );

        self::assertSame(200, $deleteResponse->getStatusCode());

        $secondCreateResponse = $this->applicationClient->makeCreateSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            $requestParameters
        );

        self::assertSame(200, $secondCreateResponse->getStatusCode());

        $secondCreateResponseData = json_decode($secondCreateResponse->getBody()->getContents(), true);
        \assert(is_array($secondCreateResponseData));
        self::assertNotSame($sourceId, $secondCreateResponseData['id']);
    }

    public function testCreateGitSourceWithNonUniqueLabel(): void
    {
        $label = 'git source label';

        $successfulResponse = $this->applicationClient->makeCreateSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            [
                OriginSourceRequest::PARAMETER_TYPE => Type::GIT->value,
                GitSourceRequest::PARAMETER_LABEL => $label,
                GitSourceRequest::PARAMETER_HOST_URL => md5((string) rand()),
                GitSourceRequest::PARAMETER_PATH => md5((string) rand()),
            ]
        );

        self::assertSame(200, $successfulResponse->getStatusCode());

        $bandRequestResponse = $this->applicationClient->makeCreateSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            [
                OriginSourceRequest::PARAMETER_TYPE => Type::GIT->value,
                GitSourceRequest::PARAMETER_LABEL => $label,
                GitSourceRequest::PARAMETER_HOST_URL => md5((string) rand()),
                GitSourceRequest::PARAMETER_PATH => md5((string) rand()),
            ]
        );

        $this->responseAsserter->assertInvalidRequestJsonResponse(
            $bandRequestResponse,
            [
                'error' => [
                    'type' => 'invalid_request',
                    'payload' => [
                        'name' => 'label',
                        'value' => $label,
                        'message' => 'This label is being used by another source belonging to this user',
                    ],
                ],
            ]
        );
    }
}
