<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Enum\Source\Type;
use App\Request\GitSourceRequest;
use App\Tests\DataProvider\CreateUpdateGitSourceDataProviderTrait;
use App\Tests\Services\SourceProvider;

abstract class AbstractUpdateGitSourceTest extends AbstractApplicationTest
{
    use CreateUpdateGitSourceDataProviderTrait;

    private SourceProvider $sourceProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $sourceProvider = self::getContainer()->get(SourceProvider::class);
        \assert($sourceProvider instanceof SourceProvider);
        $sourceProvider->setUserId(self::$authenticationConfiguration->getUser(self::USER_1_EMAIL)->id);
        $this->sourceProvider = $sourceProvider;
    }

    public function testUpdateInvalidSourceType(): void
    {
        $sourceIdentifier = SourceProvider::FILE_WITHOUT_RUN_SOURCE;

        $this->sourceProvider->initialize([$sourceIdentifier]);
        $source = $this->sourceProvider->get($sourceIdentifier);

        $response = $this->applicationClient->makeUpdateGitSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            $source->getId(),
            []
        );

        $this->responseAsserter->assertNotFoundResponse($response);
    }

    /**
     * @dataProvider createUpdateGitSourceInvalidRequestDataProvider
     *
     * @param array<string, string> $payload
     * @param array<mixed>          $expectedResponseData
     */
    public function testUpdateInvalidRequest(
        array $payload,
        array $expectedResponseData
    ): void {
        $sourceIdentifier = SourceProvider::GIT_WITH_CREDENTIALS_WITH_RUN_SOURCE;

        $this->sourceProvider->initialize([$sourceIdentifier]);
        $source = $this->sourceProvider->get($sourceIdentifier);

        $response = $this->applicationClient->makeUpdateGitSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            $source->getId(),
            $payload
        );

        $this->responseAsserter->assertInvalidRequestJsonResponse($response, $expectedResponseData);
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
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
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
        $newLabel = 'new git source label';
        $newHostUrl = 'https://new.example.com/repository.git';
        $newPath = '/new';

        return [
            Type::GIT->value . ' credentials present and empty' => [
                'sourceIdentifier' => SourceProvider::GIT_WITH_CREDENTIALS_WITH_RUN_SOURCE,
                'payload' => [
                    GitSourceRequest::PARAMETER_LABEL => $newLabel,
                    GitSourceRequest::PARAMETER_HOST_URL => $newHostUrl,
                    GitSourceRequest::PARAMETER_PATH => $newPath,
                    GitSourceRequest::PARAMETER_CREDENTIALS => null,
                ],
                'expectedResponseData' => [
                    'type' => Type::GIT->value,
                    'label' => $newLabel,
                    'host_url' => $newHostUrl,
                    'path' => $newPath,
                    'has_credentials' => false,
                ],
            ],
            Type::GIT->value . ' credentials not present' => [
                'source' => SourceProvider::GIT_WITH_CREDENTIALS_WITH_RUN_SOURCE,
                'payload' => [
                    GitSourceRequest::PARAMETER_LABEL => $newLabel,
                    GitSourceRequest::PARAMETER_HOST_URL => $newHostUrl,
                    GitSourceRequest::PARAMETER_PATH => $newPath,
                ],
                'expectedResponseData' => [
                    'type' => Type::GIT->value,
                    'label' => $newLabel,
                    'host_url' => $newHostUrl,
                    'path' => $newPath,
                    'has_credentials' => false,
                ],
            ],
        ];
    }
}
