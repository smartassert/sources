<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\FileSource;
use App\Entity\SourceOriginInterface;
use App\Entity\Suite;
use App\Enum\Source\Type;
use App\Repository\FileSourceRepository;
use App\Repository\SuiteRepository;
use App\Request\FileSourceRequest;
use App\Request\OriginSourceRequest;
use App\Tests\Services\SuiteFactory;

abstract class AbstractListSuitesTest extends AbstractApplicationTest
{
    private FileSource $source;
    private SuiteRepository $suiteRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $createSourceResponse = $this->applicationClient->makeCreateSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            [
                OriginSourceRequest::PARAMETER_TYPE => Type::FILE->value,
                FileSourceRequest::PARAMETER_LABEL => 'label',
            ]
        );
        $createSourceResponseData = json_decode($createSourceResponse->getBody()->getContents(), true);
        \assert(is_array($createSourceResponseData));
        $sourceId = $createSourceResponseData['id'] ?? null;
        \assert(is_string($sourceId));

        $fileSourceRepository = self::getContainer()->get(FileSourceRepository::class);
        \assert($fileSourceRepository instanceof FileSourceRepository);
        $source = $fileSourceRepository->find($sourceId);
        \assert($source instanceof FileSource);
        $this->source = $source;

        $suiteRepository = self::getContainer()->get(SuiteRepository::class);
        \assert($suiteRepository instanceof SuiteRepository);
        $this->suiteRepository = $suiteRepository;
    }

    /**
     * @dataProvider listSuccessDataProvider
     *
     * @param callable(SourceOriginInterface): array<string, Suite> $suitesCreator
     * @param array<string, array<mixed>>                           $expectedResponseData
     */
    public function testListSuccess(callable $suitesCreator, array $expectedResponseData): void
    {
        $suites = $suitesCreator($this->source);
        foreach ($suites as $suite) {
            $this->suiteRepository->save($suite);
        }

        $response = $this->applicationClient->makeListSuitesRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL)
        );

        foreach ($expectedResponseData as $label => $expectedSuiteData) {
            $suite = $suites[$label] ?? null;
            \assert($suite instanceof Suite);

            $expectedSuiteData['id'] = $suite->id;
            $expectedSuiteData['source_id'] = $this->source->getId();

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
                    return [];
                },
                'expectedResponseData' => [],
            ],
            'single suite' => [
                'suitesCreator' => function (SourceOriginInterface $source) {
                    return [
                        'suite1' => SuiteFactory::create($source, 'suite1', ['test1.yaml']),
                    ];
                },
                'expectedResponseData' => [
                    'suite1' => [
                        'label' => 'suite1',
                        'tests' => ['test1.yaml'],
                    ],
                ],
            ],
            'multiple suites, are ordered by label' => [
                'suitesCreator' => function (SourceOriginInterface $source) {
                    return [
                        'zebra' => SuiteFactory::create($source, 'zebra', ['test1.yaml']),
                        'apple' => SuiteFactory::create($source, 'apple', ['test1.yaml', 'test2.yaml']),
                        'bat' => SuiteFactory::create($source, 'bat', ['test2.yaml', 'test3.yaml']),
                    ];
                },
                'expectedResponseData' => [
                    'apple' => [
                        'label' => 'apple',
                        'tests' => ['test1.yaml', 'test2.yaml'],
                    ],
                    'bat' => [
                        'label' => 'bat',
                        'tests' => ['test2.yaml', 'test3.yaml'],
                    ],
                    'zebra' => [
                        'label' => 'zebra',
                        'tests' => ['test1.yaml'],
                    ],
                ],
            ],
        ];
    }
}
