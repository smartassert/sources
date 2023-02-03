<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Enum\Source\Type;
use App\Request\GitSourceRequest;
use App\Tests\Services\SourceProvider;

abstract class AbstractUpdateGitSourceTest extends AbstractApplicationTest
{
    private SourceProvider $sourceProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $sourceProvider = self::getContainer()->get(SourceProvider::class);
        \assert($sourceProvider instanceof SourceProvider);
        $sourceProvider->setUserId(self::$authenticationConfiguration->getUser()->id);
        $this->sourceProvider = $sourceProvider;
    }

    public function testUpdateInvalidSourceType(): void
    {
        $sourceIdentifier = SourceProvider::FILE_WITHOUT_RUN_SOURCE;

        $this->sourceProvider->initialize([$sourceIdentifier]);
        $source = $this->sourceProvider->get($sourceIdentifier);

        $response = $this->applicationClient->makeUpdateGitSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(),
            $source->getId(),
            []
        );

        $this->responseAsserter->assertNotFoundResponse($response);
    }

    /**
     * @dataProvider updateSourceInvalidRequestDataProvider
     *
     * @param array<string, string> $payload
     * @param array<mixed>          $expectedResponseData
     */
    public function testUpdateInvalidRequest(
        string $sourceIdentifier,
        array $payload,
        array $expectedResponseData
    ): void {
        $this->sourceProvider->initialize([$sourceIdentifier]);
        $source = $this->sourceProvider->get($sourceIdentifier);

        $response = $this->applicationClient->makeUpdateGitSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(),
            $source->getId(),
            $payload
        );

        $this->responseAsserter->assertInvalidRequestJsonResponse($response, $expectedResponseData);
    }

    /**
     * @return array<mixed>
     */
    public function updateSourceInvalidRequestDataProvider(): array
    {
        return [
            Type::GIT->value . ' missing host url' => [
                'sourceIdentifier' => SourceProvider::GIT_WITH_CREDENTIALS_WITH_RUN_SOURCE,
                'payload' => [
                    GitSourceRequest::PARAMETER_HOST_URL => '',
                    GitSourceRequest::PARAMETER_PATH => '/',
                ],
                'expectedResponseData' => [
                    'error' => [
                        'type' => 'invalid_request',
                        'payload' => [
                            'host-url' => [
                                'value' => '',
                                'message' => 'This value should not be blank.',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider updateSourceSuccessDataProvider
     *
     * @param array<string, string> $payload
     * @param array<mixed>          $expectedResponseData
     */
    public function testUpdateSuccess(
        string $sourceIdentifier,
        array $payload,
        array $expectedResponseData
    ): void {
        $this->sourceProvider->initialize([$sourceIdentifier]);
        $source = $this->sourceProvider->get($sourceIdentifier);

        $response = $this->applicationClient->makeUpdateGitSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(),
            $source->getId(),
            $payload
        );

        $expectedResponseData['id'] = $source->getId();
        $expectedResponseData['user_id'] = $source->getUserId();

        $this->responseAsserter->assertSuccessfulJsonResponse($response, $expectedResponseData);
    }

    /**
     * @return array<mixed>
     */
    public function updateSourceSuccessDataProvider(): array
    {
        $newHostUrl = 'https://new.example.com/repository.git';
        $newPath = '/new';
        $newLabel = 'new file source label';

        return [
            Type::GIT->value . ' credentials present and empty' => [
                'sourceIdentifier' => SourceProvider::GIT_WITH_CREDENTIALS_WITH_RUN_SOURCE,
                'payload' => [
                    GitSourceRequest::PARAMETER_HOST_URL => $newHostUrl,
                    GitSourceRequest::PARAMETER_PATH => $newPath,
                    GitSourceRequest::PARAMETER_CREDENTIALS => null,
                ],
                'expectedResponseData' => [
                    'type' => Type::GIT->value,
                    'host_url' => $newHostUrl,
                    'path' => $newPath,
                    'has_credentials' => false,
                ],
            ],
            Type::GIT->value . ' credentials not present' => [
                'source' => SourceProvider::GIT_WITH_CREDENTIALS_WITH_RUN_SOURCE,
                'payload' => [
                    GitSourceRequest::PARAMETER_HOST_URL => $newHostUrl,
                    GitSourceRequest::PARAMETER_PATH => $newPath,
                ],
                'expectedResponseData' => [
                    'type' => Type::GIT->value,
                    'host_url' => $newHostUrl,
                    'path' => $newPath,
                    'has_credentials' => false,
                ],
            ],
        ];
    }
}
