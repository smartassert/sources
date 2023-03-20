<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Repository\SourceRepository;
use App\Repository\SuiteRepository;
use App\Tests\Services\AuthenticationConfiguration as AuthConfig;
use App\Tests\Services\SourceOriginFactory;
use App\Tests\Services\SuiteFactory;

abstract class AbstractListSuitesTest extends AbstractApplicationTest
{
    /**
     * @dataProvider listSuccessDataProvider
     *
     * @param callable(AuthConfig, SourceRepository, SuiteRepository): array<string, array<mixed>> $suitesCreator
     * @param array<string, array<mixed>>                                                          $expectedResponseData
     */
    public function testListSuccess(callable $suitesCreator, array $expectedResponseData): void
    {
        $sourceRepository = self::getContainer()->get(SourceRepository::class);
        \assert($sourceRepository instanceof SourceRepository);

        $suiteRepository = self::getContainer()->get(SuiteRepository::class);
        \assert($suiteRepository instanceof SuiteRepository);

        $userSuitesData = $suitesCreator(
            self::$authenticationConfiguration,
            $sourceRepository,
            $suiteRepository
        );

        $response = $this->applicationClient->makeListSuitesRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL)
        );

        foreach ($expectedResponseData as $label => $expectedSuiteData) {
            $suiteData = $userSuitesData[$label];
            self::assertIsArray($suiteData);

            $expectedSuiteData = array_merge($suiteData, $expectedSuiteData);

            $expectedResponseData[$label] = $expectedSuiteData;
        }

        $this->responseAsserter->assertSuccessfulJsonResponse($response, array_values($expectedResponseData));
    }

    /**
     * @return array<mixed>
     */
    public function listSuccessDataProvider(): array
    {
        return [
            'no suites' => [
                'suitesCreator' => function () {
                },
                'expectedResponseData' => [],
            ],
            'no user suites' => [
                'suitesCreator' => function (
                    AuthConfig $authenticationConfiguration,
                    SourceRepository $sourceRepository,
                    SuiteRepository $suiteRepository
                ) {
                    $source = SourceOriginFactory::create(type: 'file');
                    $sourceRepository->save($source);

                    $suiteRepository->save(SuiteFactory::create($source));
                    $suiteRepository->save(SuiteFactory::create($source));
                    $suiteRepository->save(SuiteFactory::create($source));

                    return [];
                },
                'expectedResponseData' => [],
            ],
            'single source, single suite with no tests' => [
                'suitesCreator' => function (
                    AuthConfig $authenticationConfiguration,
                    SourceRepository $sourceRepository,
                    SuiteRepository $suiteRepository
                ) {
                    $source = SourceOriginFactory::create(
                        type: 'file',
                        userId: $authenticationConfiguration->getUser(self::USER_1_EMAIL)->id,
                        label: 'source one',
                    );

                    $sourceRepository->save($source);

                    $suite = SuiteFactory::create(source: $source, label: 'source one suite one');
                    $suiteRepository->save($suite);

                    return [
                        'source one suite one' => [
                            'id' => $suite->id,
                            'source_id' => $source->getId(),
                        ],
                    ];
                },
                'expectedResponseData' => [
                    'source one suite one' => [
                        'label' => 'source one suite one',
                        'tests' => [],
                    ],
                ],
            ],
            'two sources, one suite per source' => [
                'suitesCreator' => function (
                    AuthConfig $authenticationConfiguration,
                    SourceRepository $sourceRepository,
                    SuiteRepository $suiteRepository
                ) {
                    $source1 = SourceOriginFactory::create(
                        type: 'file',
                        userId: $authenticationConfiguration->getUser(self::USER_1_EMAIL)->id,
                        label: 'source one',
                    );

                    $sourceRepository->save($source1);

                    $suite1 = SuiteFactory::create(
                        source: $source1,
                        label: 'source one suite one',
                        tests: ['source_one_suite_one/test1.yaml', 'source_one_suite_one/test2.yaml']
                    );

                    $suiteRepository->save($suite1);

                    $source2 = SourceOriginFactory::create(
                        type: 'file',
                        userId: $authenticationConfiguration->getUser(self::USER_1_EMAIL)->id,
                        label: 'source two',
                    );

                    $sourceRepository->save($source2);

                    $suite2 = SuiteFactory::create(
                        source: $source2,
                        label: 'source two suite one',
                        tests: ['source_two_suite_one/test1.yaml']
                    );

                    $suiteRepository->save($suite2);

                    return [
                        'source one suite one' => [
                            'id' => $suite1->id,
                            'source_id' => $source1->getId(),
                        ],
                        'source two suite one' => [
                            'id' => $suite2->id,
                            'source_id' => $source2->getId(),
                        ],
                    ];
                },
                'expectedResponseData' => [
                    'source one suite one' => [
                        'label' => 'source one suite one',
                        'tests' => [
                            'source_one_suite_one/test1.yaml',
                            'source_one_suite_one/test2.yaml'
                        ],
                    ],
                    'source two suite one' => [
                        'label' => 'source two suite one',
                        'tests' => [
                            'source_two_suite_one/test1.yaml'
                        ],
                    ],
                ],
            ],
            'multiple suites, are ordered by id' => [
                'suitesCreator' => function (
                    AuthConfig $authenticationConfiguration,
                    SourceRepository $sourceRepository,
                    SuiteRepository $suiteRepository
                ) {
                    $zebraSource = SourceOriginFactory::create(
                        type: 'file',
                        userId: $authenticationConfiguration->getUser(self::USER_1_EMAIL)->id,
                        label: 'zebra one',
                    );

                    $sourceRepository->save($zebraSource);

                    $zebraSuite = SuiteFactory::create(
                        source: $zebraSource,
                        label: 'zebra',
                        tests: ['test1.yaml']
                    );

                    $suiteRepository->save($zebraSuite);

                    $appleAndBatSource = SourceOriginFactory::create(
                        type: 'file',
                        userId: $authenticationConfiguration->getUser(self::USER_1_EMAIL)->id,
                        label: 'apple and bat source',
                    );

                    $sourceRepository->save($appleAndBatSource);

                    $appleSuite = SuiteFactory::create(
                        source: $appleAndBatSource,
                        label: 'apple',
                        tests: ['test1.yaml', 'test2.yaml']
                    );

                    $suiteRepository->save($appleSuite);

                    $batSuite = SuiteFactory::create(
                        source: $appleAndBatSource,
                        label: 'bat',
                        tests: ['test2.yaml', 'test3.yaml']
                    );

                    $suiteRepository->save($batSuite);

                    return [
                        'zebra' => [
                            'id' => $zebraSuite->id,
                            'source_id' => $zebraSource->getId(),
                        ],
                        'apple' => [
                            'id' => $appleSuite->id,
                            'source_id' => $appleAndBatSource->getId(),
                        ],
                        'bat' => [
                            'id' => $batSuite->id,
                            'source_id' => $appleAndBatSource->getId(),
                        ],
                    ];
                },
                'expectedResponseData' => [
                    'zebra' => [
                        'label' => 'zebra',
                        'tests' => ['test1.yaml'],
                    ],
                    'apple' => [
                        'label' => 'apple',
                        'tests' => ['test1.yaml', 'test2.yaml'],
                    ],
                    'bat' => [
                        'label' => 'bat',
                        'tests' => ['test2.yaml', 'test3.yaml'],
                    ],
                ],
            ],
        ];
    }
}
