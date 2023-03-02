<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\SourceInterface;
use App\Enum\Source\Type;
use App\Repository\SourceRepository;
use App\Request\GitSourceRequest;
use App\Request\OriginSourceRequest;
use App\Tests\DataProvider\CreateUpdateGitSourceDataProviderTrait;

abstract class AbstractCreateGitSourceTest extends AbstractApplicationTest
{
    use CreateUpdateGitSourceDataProviderTrait;

    /**
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
     * @dataProvider createSourceSuccessDataProvider
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
    public function createSourceSuccessDataProvider(): array
    {
        $label = 'git source label';
        $hostUrl = 'https://example.com/repository.git';
        $path = '/';
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

    public function testCreateIsIdempotent(): void
    {
        $label = 'git source label';
        $hostUrl = 'https://example.com/repository.git';
        $path = '/';

        $firstResponse = $this->applicationClient->makeCreateSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            [
                OriginSourceRequest::PARAMETER_TYPE => Type::GIT->value,
                GitSourceRequest::PARAMETER_LABEL => $label,
                GitSourceRequest::PARAMETER_HOST_URL => $hostUrl,
                GitSourceRequest::PARAMETER_PATH => $path,
            ]
        );

        self::assertSame(200, $firstResponse->getStatusCode());

        $secondResponse = $this->applicationClient->makeCreateSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            [
                OriginSourceRequest::PARAMETER_TYPE => Type::GIT->value,
                GitSourceRequest::PARAMETER_LABEL => $label,
                GitSourceRequest::PARAMETER_HOST_URL => $hostUrl,
                GitSourceRequest::PARAMETER_PATH => $path,
            ]
        );

        self::assertSame(200, $secondResponse->getStatusCode());
        self::assertSame($firstResponse->getBody()->getContents(), $secondResponse->getBody()->getContents());
    }

    public function testCreateWithNonUniqueLabel(): void
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
                        'message' => 'This label is being used by another git source belonging to this user',
                    ],
                ],
            ]
        );
    }
}
