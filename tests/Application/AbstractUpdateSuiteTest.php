<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\Suite;
use App\Repository\SuiteRepository;
use App\Request\SuiteRequest;
use App\Tests\DataProvider\CreateUpdateSuiteDataProviderTrait;
use App\Tests\Services\StringFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Uid\Ulid;

abstract class AbstractUpdateSuiteTest extends AbstractSuiteTest
{
    use CreateUpdateSuiteDataProviderTrait;

    private string $secondarySourceId;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->secondarySourceId = $this->createSource(self::USER_1_EMAIL);
    }

    /**
     * @param array<mixed> $requestParameters
     * @param array<mixed> $expectedResponseData
     */
    #[DataProvider('createUpdateSuiteInvalidRequestDataProvider')]
    public function testUpdateInvalidSuiteRequest(array $requestParameters, array $expectedResponseData): void
    {
        $suiteId = $this->createSuite($this->sourceId, StringFactory::createRandom(), []);

        $response = $this->applicationClient->makeUpdateSuiteRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $suiteId,
            array_merge(
                [
                    SuiteRequest::PARAMETER_SOURCE_ID => $this->sourceId,
                ],
                $requestParameters
            )
        );

        self::assertSame(400, $response->getStatusCode());
        self::assertSame('application/json', $response->getHeaderLine('content-type'));

        self::assertJsonStringEqualsJsonString(
            (string) json_encode($expectedResponseData),
            $response->getBody()->getContents(),
        );
    }

    public function testUpdateNewLabelNotUnique(): void
    {
        $suiteRepository = self::getContainer()->get(SuiteRepository::class);
        \assert($suiteRepository instanceof SuiteRepository);

        $suiteLabel1 = StringFactory::createRandom();
        $suiteLabel2 = StringFactory::createRandom();

        $suiteId = $this->createSuite($this->sourceId, $suiteLabel1, ['test.yaml']);
        $this->createSuite($this->sourceId, $suiteLabel2, ['test.yaml']);

        self::assertSame(1, $suiteRepository->count(['label' => $suiteLabel1]));

        $updateResponse = $this->applicationClient->makeUpdateSuiteRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $suiteId,
            [
                SuiteRequest::PARAMETER_SOURCE_ID => $this->sourceId,
                SuiteRequest::PARAMETER_LABEL => $suiteLabel2,
                SuiteRequest::PARAMETER_TESTS => ['test.yaml'],
            ]
        );

        $expectedResponseData = [
            'class' => 'duplicate',
            'parameter' => [
                'name' => 'label',
                'value' => $suiteLabel2,
            ],
        ];

        self::assertJsonStringEqualsJsonString(
            (string) json_encode($expectedResponseData),
            $updateResponse->getBody()->getContents(),
        );
    }

    /**
     * @param callable(string, string): string                $sourceIdSelector
     * @param callable(string, string): array<string, string> $updateRequestParametersCreator
     * @param string[]                                        $initialSuiteTests
     */
    #[DataProvider('updateSuccessDataProvider')]
    public function testUpdateSuccess(
        callable $sourceIdSelector,
        string $initialSuiteLabel,
        array $initialSuiteTests,
        callable $updateRequestParametersCreator,
    ): void {
        $sourceId = $sourceIdSelector($this->sourceId, $this->secondarySourceId);
        $suiteId = $this->createSuite($sourceId, $initialSuiteLabel, $initialSuiteTests);

        $updateRequestParameters = $updateRequestParametersCreator($this->sourceId, $this->secondarySourceId);

        $response = $this->applicationClient->makeUpdateSuiteRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $suiteId,
            array_merge(['source_id' => $this->sourceId], $updateRequestParameters),
        );

        $expected = array_merge(
            $updateRequestParameters,
            [
                'id' => $suiteId,
            ]
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/json', $response->getHeaderLine('content-type'));
        self::assertJsonStringEqualsJsonString(
            (string) json_encode($expected),
            $response->getBody()->getContents()
        );
    }

    /**
     * @return array<mixed>
     */
    public static function updateSuccessDataProvider(): array
    {
        $primarySourceIdSelector = function (string $sourceId): string {
            return $sourceId;
        };

        return [
            'source unchanged, label unchanged, tests change from empty to non-empty' => [
                'sourceIdSelector' => $primarySourceIdSelector,
                'initialSuiteLabel' => 'label',
                'initialSuiteTests' => [],
                'updateRequestParametersCreator' => function (string $sourceId) {
                    return [
                        SuiteRequest::PARAMETER_SOURCE_ID => $sourceId,
                        SuiteRequest::PARAMETER_LABEL => 'label',
                        SuiteRequest::PARAMETER_TESTS => [
                            'Test/test' . StringFactory::createRandom() . '.yaml',
                            'Test/test' . StringFactory::createRandom() . '.yaml',
                        ],
                    ];
                },
            ],
            'source unchanged, label unchanged, tests change from non-empty to empty' => [
                'sourceIdSelector' => $primarySourceIdSelector,
                'initialSuiteLabel' => 'label',
                'initialSuiteTests' => [
                    'Test/test' . StringFactory::createRandom() . '.yaml',
                ],
                'updateRequestParametersCreator' => function (string $sourceId) {
                    return [
                        SuiteRequest::PARAMETER_SOURCE_ID => $sourceId,
                        SuiteRequest::PARAMETER_LABEL => 'label',
                        SuiteRequest::PARAMETER_TESTS => [],
                    ];
                },
            ],
            'source unchanged, label changed, tests remain same and empty' => [
                'sourceIdSelector' => $primarySourceIdSelector,
                'initialSuiteLabel' => 'label',
                'initialSuiteTests' => [],
                'updateRequestParametersCreator' => function (string $sourceId) {
                    return [
                        SuiteRequest::PARAMETER_SOURCE_ID => $sourceId,
                        SuiteRequest::PARAMETER_LABEL => 'new label',
                        SuiteRequest::PARAMETER_TESTS => [],
                    ];
                },
            ],
            'source unchanged, label changed, tests remain same and non-empty' => [
                'sourceIdSelector' => $primarySourceIdSelector,
                'initialSuiteLabel' => 'label',
                'initialSuiteTests' => [
                    'Test/test1.yaml',
                    'Test/test2.yaml',
                ],
                'updateRequestParametersCreator' => function (string $sourceId) {
                    return [
                        SuiteRequest::PARAMETER_SOURCE_ID => $sourceId,
                        SuiteRequest::PARAMETER_LABEL => 'new label',
                        SuiteRequest::PARAMETER_TESTS => [
                            'Test/test1.yaml',
                            'Test/test2.yaml',
                        ],
                    ];
                },
            ],
            'all changed' => [
                'sourceIdSelector' => $primarySourceIdSelector,
                'initialSuiteLabel' => 'original label',
                'initialSuiteTests' => [
                    'Test/test1.yaml',
                ],
                'updateRequestParametersCreator' => function (string $sourceId, string $secondarySourceId) {
                    return [
                        SuiteRequest::PARAMETER_SOURCE_ID => $secondarySourceId,
                        SuiteRequest::PARAMETER_LABEL => 'new label',
                        SuiteRequest::PARAMETER_TESTS => [
                            'Test/test2.yaml',
                        ],
                    ];
                },
            ],
        ];
    }

    public function testUpdateIsIdempotent(): void
    {
        $initialLabel = StringFactory::createRandom();
        $initialTests = ['Test/test' . StringFactory::createRandom() . '.yaml'];

        $newLabel = StringFactory::createRandom();
        $newTests = ['Test/test' . StringFactory::createRandom() . '.yaml'];

        $suiteId = $this->createSuite($this->sourceId, $initialLabel, $initialTests);

        $updateParameters = [
            SuiteRequest::PARAMETER_SOURCE_ID => $this->sourceId,
            SuiteRequest::PARAMETER_LABEL => $newLabel,
            SuiteRequest::PARAMETER_TESTS => $newTests,
        ];

        $firstUpdateResponse = $this->applicationClient->makeUpdateSuiteRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $suiteId,
            $updateParameters,
        );

        self::assertSame(200, $firstUpdateResponse->getStatusCode());

        $secondUpdateResponse = $this->applicationClient->makeUpdateSuiteRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $suiteId,
            $updateParameters,
        );

        self::assertSame(200, $secondUpdateResponse->getStatusCode());
        self::assertSame(
            $firstUpdateResponse->getBody()->getContents(),
            $secondUpdateResponse->getBody()->getContents()
        );
    }

    public function testUpdateDeletedSuite(): void
    {
        $suiteId = $this->createSuite($this->sourceId, 'label', []);

        $this->applicationClient->makeDeleteSuiteRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $suiteId
        );

        $response = $this->applicationClient->makeUpdateSuiteRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $suiteId,
            [
                SuiteRequest::PARAMETER_SOURCE_ID => $this->sourceId,
                SuiteRequest::PARAMETER_LABEL => StringFactory::createRandom(),
                SuiteRequest::PARAMETER_TESTS => [],
            ]
        );

        $expectedResponseData = [
            'class' => 'modify_read_only',
            'entity' => [
                'id' => $suiteId,
                'type' => 'suite',
            ],
        ];

        self::assertJsonStringEqualsJsonString(
            (string) json_encode($expectedResponseData),
            $response->getBody()->getContents(),
        );
    }

    public function testUpdateSuiteWithLabelOfDeletedSuite(): void
    {
        $deletedSuiteLabel = 'deleted suite label';

        $deletedSuiteId = $this->createSuite($this->sourceId, $deletedSuiteLabel, []);

        $suiteRepository = self::getContainer()->get(SuiteRepository::class);
        \assert($suiteRepository instanceof SuiteRepository);
        $suite = $suiteRepository->find($deletedSuiteId);
        \assert($suite instanceof Suite);
        $suiteRepository->delete($suite);

        $suiteId = $this->createSuite($this->sourceId, StringFactory::createRandom(), []);

        $updateResponse = $this->applicationClient->makeUpdateSuiteRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $suiteId,
            [
                SuiteRequest::PARAMETER_SOURCE_ID => $this->sourceId,
                SuiteRequest::PARAMETER_LABEL => $deletedSuiteLabel,
                SuiteRequest::PARAMETER_TESTS => [],
            ],
        );

        self::assertSame(200, $updateResponse->getStatusCode());
    }

    public function testUpdateSuiteSourceNotFound(): void
    {
        $suiteId = $this->createSuite($this->sourceId, StringFactory::createRandom(), []);

        $response = $this->applicationClient->makeUpdateSuiteRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $suiteId,
            [
                SuiteRequest::PARAMETER_SOURCE_ID => StringFactory::createRandom(),
                SuiteRequest::PARAMETER_LABEL => StringFactory::createRandom(),
                SuiteRequest::PARAMETER_TESTS => [],
            ],
        );

        self::assertSame(403, $response->getStatusCode());
    }

    public function testUpdateSuiteSuiteNotFound(): void
    {
        $response = $this->applicationClient->makeUpdateSuiteRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            (string) new Ulid(),
            [
                SuiteRequest::PARAMETER_SOURCE_ID => $this->sourceId,
                SuiteRequest::PARAMETER_LABEL => StringFactory::createRandom(),
                SuiteRequest::PARAMETER_TESTS => [],
            ],
        );

        self::assertSame(403, $response->getStatusCode());
    }

    /**
     * @param string[] $tests
     */
    private function createSuite(string $sourceId, string $label, array $tests): string
    {
        $response = $this->applicationClient->makeCreateSuiteRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            [
                SuiteRequest::PARAMETER_SOURCE_ID => $sourceId,
                SuiteRequest::PARAMETER_LABEL => $label,
                SuiteRequest::PARAMETER_TESTS => $tests,
            ]
        );

        $responseData = json_decode($response->getBody()->getContents(), true);
        \assert(is_array($responseData));
        $suiteId = $responseData['id'] ?? null;
        \assert(is_string($suiteId));

        return $suiteId;
    }
}
