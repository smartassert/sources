<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\RunSource;
use App\Enum\RunSource\FailureReason;
use App\Enum\RunSource\State;
use App\Enum\Source\Type;
use App\Model\EntityId;
use App\Tests\Services\SourceProvider;

abstract class AbstractGetSourceTest extends AbstractApplicationTest
{
    public function testGetSourceNotFound(): void
    {
        $response = $this->applicationClient->makeGetSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(),
            EntityId::create()
        );

        $this->responseAsserter->assertNotFoundResponse($response);
    }

    /**
     * @dataProvider getSourceSuccessDataProvider
     *
     * @param array<mixed> $expectedResponseData
     */
    public function testGetSuccess(string $sourceIdentifier, array $expectedResponseData): void
    {
        $sourceProvider = self::getContainer()->get(SourceProvider::class);
        \assert($sourceProvider instanceof SourceProvider);
        $sourceProvider->setUserId(self::$authenticationConfiguration->getUser()->id);
        $sourceProvider->initialize();

        $source = $sourceProvider->get($sourceIdentifier);

        $response = $this->applicationClient->makeGetSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(),
            $source->getId()
        );

        $expectedResponseData['id'] = $source->getId();
        $expectedResponseData['user_id'] = $source->getUserId();

        if ($source instanceof RunSource) {
            $expectedResponseData['parent'] = $source->getParent()?->getId();
        }

        $this->responseAsserter->assertSuccessfulJsonResponse($response, $expectedResponseData);
    }

    /**
     * @return array<mixed>
     */
    public function getSourceSuccessDataProvider(): array
    {
        return [
            'git source with credentials' => [
                'sourceIdentifier' => SourceProvider::GIT_WITH_CREDENTIALS_WITH_RUN_SOURCE,
                'expectedResponseData' => [
                    'type' => Type::GIT->value,
                    'host_url' => 'http://example.com/with-credentials.git',
                    'path' => '/',
                    'has_credentials' => true,
                ],
            ],
            'git source without credentials' => [
                'sourceIdentifier' => SourceProvider::GIT_WITHOUT_CREDENTIALS_WITHOUT_RUN_SOURCE,
                'expectedResponseData' => [
                    'type' => Type::GIT->value,
                    'host_url' => 'http://example.com/without-credentials.git',
                    'path' => '/',
                    'has_credentials' => false,
                ],
            ],
            'file with run source' => [
                'sourceIdentifier' => SourceProvider::FILE_WITH_RUN_SOURCE,
                'expectedResponseData' => [
                    'type' => Type::FILE->value,
                    'label' => 'with run source',
                ],
            ],
            'run' => [
                'sourceIdentifier' => SourceProvider::RUN_WITH_FILE_PARENT,
                'expectedResponseData' => [
                    'type' => Type::RUN->value,
                    'parameters' => [],
                    'state' => State::REQUESTED->value,
                ],
            ],
            'run: preparation failed' => [
                'sourceIdentifier' => SourceProvider::RUN_FAILED,
                'expectedResponseData' => [
                    'type' => Type::RUN->value,
                    'parameters' => [],
                    'state' => State::FAILED->value,
                    'failure_reason' => FailureReason::GIT_CLONE->value,
                    'failure_message' => 'fatal: repository \'http://example.com/with-credentials.git\' not found',
                ],
            ],
        ];
    }
}
