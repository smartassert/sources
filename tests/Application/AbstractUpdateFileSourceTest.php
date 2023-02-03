<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Enum\Source\Type;
use App\Request\FileSourceRequest;
use App\Request\SourceRequestInterface;
use App\Tests\Services\SourceProvider;

abstract class AbstractUpdateFileSourceTest extends AbstractApplicationTest
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

        $response = $this->applicationClient->makeUpdateFileSourceRequest(
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
            Type::FILE->value . ' missing label' => [
                'sourceIdentifier' => SourceProvider::GIT_WITH_CREDENTIALS_WITH_RUN_SOURCE,
                'payload' => [
                    SourceRequestInterface::PARAMETER_TYPE => Type::FILE->value,
                ],
                'expectedResponseData' => [
                    'error' => [
                        'type' => 'invalid_request',
                        'payload' => [
                            'label' => [
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
                    SourceRequestInterface::PARAMETER_TYPE => Type::FILE->value,
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
