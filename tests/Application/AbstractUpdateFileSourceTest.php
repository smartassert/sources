<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Enum\Source\Type;
use App\Request\FileSourceRequest;
use App\Tests\DataProvider\CreateUpdateFileSourceDataProviderTrait;
use App\Tests\Services\SourceProvider;

abstract class AbstractUpdateFileSourceTest extends AbstractApplicationTest
{
    use CreateUpdateFileSourceDataProviderTrait;

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
        $sourceIdentifier = SourceProvider::GIT_WITHOUT_CREDENTIALS_WITHOUT_RUN_SOURCE;

        $this->sourceProvider->initialize([$sourceIdentifier]);
        $source = $this->sourceProvider->get($sourceIdentifier);

        $response = $this->applicationClient->makeUpdateFileSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(),
            $source->getId(),
            []
        );

        $this->responseAsserter->assertNotFoundResponse($response);
    }

    /**
     * @dataProvider createUpdateFileSourceInvalidRequestDataProvider
     *
     * @param array<string, string> $payload
     * @param array<mixed>          $expectedResponseData
     */
    public function testUpdateInvalidRequest(
        array $payload,
        array $expectedResponseData
    ): void {
        $sourceIdentifier = SourceProvider::FILE_WITHOUT_RUN_SOURCE;

        $this->sourceProvider->initialize([$sourceIdentifier]);
        $source = $this->sourceProvider->get($sourceIdentifier);

        $response = $this->applicationClient->makeUpdateFileSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(),
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

        $response = $this->applicationClient->makeUpdateFileSourceRequest(
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
        $newLabel = 'new file source label';

        return [
            Type::FILE->value => [
                'sourceIdentifier' => SourceProvider::FILE_WITHOUT_RUN_SOURCE,
                'payload' => [
                    FileSourceRequest::PARAMETER_LABEL => $newLabel,
                ],
                'expectedResponseData' => [
                    'type' => Type::FILE->value,
                    'label' => $newLabel,
                ],
            ],
        ];
    }
}
