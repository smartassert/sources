<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Entity\SourceInterface;
use App\Tests\DataProvider\TestConstants;
use App\Tests\Model\UserId;
use App\Tests\Services\SourceUserIdMutator;

abstract class AbstractListSourcesTest extends AbstractApplicationTest
{
    private SourceUserIdMutator $sourceUserIdMutator;

    protected function setUp(): void
    {
        parent::setUp();

        $sourceUserIdMutator = self::getContainer()->get(SourceUserIdMutator::class);
        \assert($sourceUserIdMutator instanceof SourceUserIdMutator);
        $this->sourceUserIdMutator = $sourceUserIdMutator;
    }

    /**
     * @dataProvider listSuccessDataProvider
     *
     * @param SourceInterface[]        $sources
     * @param array<int, array<mixed>> $expectedResponseData
     */
    public function testListSuccess(array $sources, array $expectedResponseData): void
    {
        foreach ($sources as $source) {
            $source = $this->sourceUserIdMutator->setSourceUserId($source);
            $this->store->add($source);
        }

        $response = $this->applicationClient->makeListSourcesRequest($this->authenticationConfiguration->validToken);

        $expectedResponseData = $this->sourceUserIdMutator->setSourceDataCollectionUserId($expectedResponseData);

        $this->responseAsserter->assertSuccessfulJsonResponse($response, $expectedResponseData);
    }

    /**
     * @return array<mixed>
     */
    public function listSuccessDataProvider(): array
    {
        $userFileSources = [
            new FileSource(TestConstants::AUTHENTICATED_USER_ID_PLACEHOLDER, 'file source label'),
        ];

        $userGitSources = [
            new GitSource(TestConstants::AUTHENTICATED_USER_ID_PLACEHOLDER, 'https://example.com/repository.git'),
        ];

        $userRunSources = [
            new RunSource($userFileSources[0]),
            new RunSource($userGitSources[0]),
        ];

        return [
            'no sources' => [
                'sources' => [],
                'expectedResponseData' => [],
            ],
            'has file, git and run sources, no user match' => [
                'sources' => [
                    new FileSource(UserId::create(), 'file source label'),
                    new GitSource(UserId::create(), 'https://example.com/repository.git'),
                    new RunSource(
                        new FileSource(UserId::create(), 'file source label'),
                    ),
                ],
                'expectedResponseData' => [],
            ],
            'has file and git sources for correct user only' => [
                'sources' => [
                    $userFileSources[0],
                    $userGitSources[0],
                ],
                'expectedResponseData' => [
                    $userFileSources[0]->jsonSerialize(),
                    $userGitSources[0]->jsonSerialize(),
                ],
            ],
            'has file, git and run sources for correct user only' => [
                'sources' => [
                    $userFileSources[0],
                    $userGitSources[0],
                    $userRunSources[0],
                    $userRunSources[1],
                ],
                'expectedResponseData' => [
                    $userFileSources[0]->jsonSerialize(),
                    $userGitSources[0]->jsonSerialize(),
                ],
            ],
            'has file, git and run sources for mixed users' => [
                'sources' => [
                    $userFileSources[0],
                    new FileSource(UserId::create(), 'file source label'),
                    $userGitSources[0],
                    new GitSource(UserId::create(), 'https://example.com/repository.git'),
                    $userRunSources[0],
                    $userRunSources[1],
                    new RunSource(
                        new FileSource(UserId::create(), 'file source label')
                    ),
                    new RunSource(
                        new GitSource(UserId::create(), 'https://example.com/repository.git')
                    )
                ],
                'expectedResponseData' => [
                    $userFileSources[0]->jsonSerialize(),
                    $userGitSources[0]->jsonSerialize(),
                ],
            ],
        ];
    }
}
