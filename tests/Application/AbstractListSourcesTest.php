<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\SourceInterface;
use App\Enum\Source\Type;
use App\Tests\Services\SourceProvider;

abstract class AbstractListSourcesTest extends AbstractApplicationTest
{
    /**
     * @dataProvider listSuccessDataProvider
     *
     * @param string[]                    $sourceIdentifiers
     * @param array<string, array<mixed>> $expectedResponseData
     */
    public function testListSuccess(array $sourceIdentifiers, array $expectedResponseData): void
    {
        $sourceProvider = self::getContainer()->get(SourceProvider::class);
        \assert($sourceProvider instanceof SourceProvider);
        $sourceProvider->setUserId(self::$authenticationConfiguration->getUser(self::USER_1_EMAIL)->id);
        $sourceProvider->initialize($sourceIdentifiers);

        $sources = [];
        foreach ($sourceIdentifiers as $sourceIdentifier) {
            $sources[$sourceIdentifier] = $sourceProvider->get($sourceIdentifier);
        }

        $response = $this->applicationClient->makeListSourcesRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL)
        );

        foreach ($expectedResponseData as $sourceIdentifier => $expectedSourceData) {
            $source = $sources[$sourceIdentifier];
            \assert($source instanceof SourceInterface);

            $expectedSourceData['id'] = $source->getId();
            $expectedSourceData['user_id'] = $source->getUserId();

            $expectedResponseData[$sourceIdentifier] = $expectedSourceData;
        }

        $this->responseAsserter->assertSuccessfulJsonResponse($response, array_values($expectedResponseData));
    }

    /**
     * @return array<mixed>
     */
    public function listSuccessDataProvider(): array
    {
        return [
            'no sources' => [
                'sourceIdentifiers' => [],
                'expectedResponseData' => [],
            ],
            'has file, git and run sources, no user match' => [
                'sourceIdentifiers' => [
                    SourceProvider::FILE_DIFFERENT_USER,
                    SourceProvider::GIT_DIFFERENT_USER,
                    SourceProvider::RUN_DIFFERENT_USER,
                ],
                'expectedResponseData' => [],
            ],
            'has file and git sources for correct user only' => [
                'sourceIdentifiers' => [
                    SourceProvider::FILE_WITHOUT_RUN_SOURCE,
                    SourceProvider::GIT_WITHOUT_CREDENTIALS_WITHOUT_RUN_SOURCE,
                ],
                'expectedResponseData' => [
                    SourceProvider::FILE_WITHOUT_RUN_SOURCE => [
                        'type' => Type::FILE->value,
                        'label' => 'without run source',
                    ],
                    SourceProvider::GIT_WITHOUT_CREDENTIALS_WITHOUT_RUN_SOURCE => [
                        'type' => Type::GIT->value,
                        'label' => 'git source without credentials without run source',
                        'host_url' => 'http://example.com/without-credentials.git',
                        'path' => '/',
                        'has_credentials' => false,
                    ],
                ],
            ],
            'has file, git and run sources for correct user only' => [
                'sourceIdentifiers' => [
                    SourceProvider::FILE_WITH_RUN_SOURCE,
                    SourceProvider::GIT_WITHOUT_CREDENTIALS_WITHOUT_RUN_SOURCE,
                    SourceProvider::RUN_WITH_FILE_PARENT,
                ],
                'expectedResponseData' => [
                    SourceProvider::FILE_WITH_RUN_SOURCE => [
                        'type' => Type::FILE->value,
                        'label' => 'with run source',
                    ],
                    SourceProvider::GIT_WITHOUT_CREDENTIALS_WITHOUT_RUN_SOURCE => [
                        'type' => Type::GIT->value,
                        'label' => 'git source without credentials without run source',
                        'host_url' => 'http://example.com/without-credentials.git',
                        'path' => '/',
                        'has_credentials' => false,
                    ],
                ],
            ],
            'has file, git and run sources for mixed users' => [
                'sourceIdentifiers' => [
                    SourceProvider::FILE_DIFFERENT_USER,
                    SourceProvider::GIT_DIFFERENT_USER,
                    SourceProvider::FILE_WITH_RUN_SOURCE,
                    SourceProvider::GIT_WITHOUT_CREDENTIALS_WITHOUT_RUN_SOURCE,
                    SourceProvider::RUN_DIFFERENT_USER,
                    SourceProvider::RUN_WITH_FILE_PARENT,
                ],
                'expectedResponseData' => [
                    SourceProvider::FILE_WITH_RUN_SOURCE => [
                        'type' => Type::FILE->value,
                        'label' => 'with run source',
                    ],
                    SourceProvider::GIT_WITHOUT_CREDENTIALS_WITHOUT_RUN_SOURCE => [
                        'type' => Type::GIT->value,
                        'label' => 'git source without credentials without run source',
                        'host_url' => 'http://example.com/without-credentials.git',
                        'path' => '/',
                        'has_credentials' => false,
                    ],
                ],
            ],
        ];
    }
}
