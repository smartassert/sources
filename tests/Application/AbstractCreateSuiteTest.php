<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\Suite;
use App\Repository\SuiteRepository;
use App\Request\SuiteRequest;
use App\Tests\DataProvider\CreateUpdateSuiteDataProviderTrait;
use App\Tests\Services\StringFactory;
use PHPUnit\Framework\Attributes\DataProvider;

abstract class AbstractCreateSuiteTest extends AbstractSuiteTest
{
    use CreateUpdateSuiteDataProviderTrait;

    /**
     * @param array<mixed> $requestParameters
     * @param array<mixed> $expectedResponseData
     */
    #[DataProvider('createUpdateSuiteInvalidRequestDataProvider')]
    public function testCreateInvalidSuiteRequest(array $requestParameters, array $expectedResponseData): void
    {
        $response = $this->applicationClient->makeCreateSuiteRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
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

    /**
     * @param array<string, string> $requestParameters
     */
    #[DataProvider('createSuccessDataProvider')]
    public function testCreateSuccess(array $requestParameters): void
    {
        $response = $this->applicationClient->makeCreateSuiteRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            array_merge(
                [
                    SuiteRequest::PARAMETER_SOURCE_ID => $this->sourceId,
                ],
                $requestParameters
            )
        );

        $suites = [];
        $repository = self::getContainer()->get(SuiteRepository::class);
        if ($repository instanceof SuiteRepository) {
            $suites = $repository->findAll();
        }

        self::assertIsArray($suites);
        self::assertCount(1, $suites);

        $suite = $suites[0];
        self::assertInstanceOf(Suite::class, $suite);
        self::assertSame(
            self::$users->get(self::USER_1_EMAIL)['id'],
            $suite->getUserId()
        );

        $expected = array_merge(
            $requestParameters,
            [
                'id' => $suite->id,
                'source_id' => $this->sourceId,
            ]
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/json', $response->getHeaderLine('content-type'));
        self::assertJsonStringEqualsJsonString((string) json_encode($expected), $response->getBody()->getContents());
    }

    /**
     * @return array<mixed>
     */
    public static function createSuccessDataProvider(): array
    {
        return [
            'no tests' => [
                'requestParameters' => [
                    SuiteRequest::PARAMETER_LABEL => StringFactory::createRandom(),
                    SuiteRequest::PARAMETER_TESTS => [],
                ],
            ],
            'has tests' => [
                'requestParameters' => [
                    SuiteRequest::PARAMETER_LABEL => StringFactory::createRandom(),
                    SuiteRequest::PARAMETER_TESTS => [
                        'Test/test' . StringFactory::createRandom() . '.yaml',
                        'Test/test' . StringFactory::createRandom() . '.yaml',
                        'Test/test' . StringFactory::createRandom() . '.yaml',
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array<mixed> $requestParameters
     */
    #[DataProvider('createIsIdempotentDataProvider')]
    public function testCreateIsIdempotent(array $requestParameters): void
    {
        $requestParameters = array_merge([SuiteRequest::PARAMETER_SOURCE_ID => $this->sourceId], $requestParameters);

        $firstResponse = $this->applicationClient->makeCreateSuiteRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $requestParameters
        );

        self::assertSame(200, $firstResponse->getStatusCode());

        $secondResponse = $this->applicationClient->makeCreateSuiteRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $requestParameters
        );

        self::assertSame(200, $secondResponse->getStatusCode());
        self::assertSame($firstResponse->getBody()->getContents(), $secondResponse->getBody()->getContents());
    }

    /**
     * @return array<mixed>
     */
    public static function createIsIdempotentDataProvider(): array
    {
        return [
            'has tests' => [
                'requestParameters' => [
                    SuiteRequest::PARAMETER_LABEL => 'label',
                    SuiteRequest::PARAMETER_TESTS => [
                        'Test/test' . StringFactory::createRandom() . '.yaml',
                    ],
                ],
            ],
            'no tests' => [
                'requestParameters' => [
                    SuiteRequest::PARAMETER_LABEL => 'label',
                    SuiteRequest::PARAMETER_TESTS => [],
                ],
            ],
        ];
    }

    public function testCreateMultipleSuitesForSameSource(): void
    {
        $labels = [
            StringFactory::createRandom(),
            StringFactory::createRandom(),
            StringFactory::createRandom(),
        ];

        $createdSuiteCount = 0;
        $previousSuiteId = null;
        foreach ($labels as $label) {
            $response = $this->applicationClient->makeCreateSuiteRequest(
                self::$apiTokens->get(self::USER_1_EMAIL),
                [
                    SuiteRequest::PARAMETER_SOURCE_ID => $this->sourceId,
                    SuiteRequest::PARAMETER_LABEL => $label,
                    SuiteRequest::PARAMETER_TESTS => [
                        'Test/test' . StringFactory::createRandom() . '.yaml',
                    ],
                ]
            );

            $responseData = json_decode($response->getBody()->getContents(), true);
            \assert(is_array($responseData));
            $suiteId = $responseData['id'] ?? null;
            \assert(is_string($suiteId));

            self::assertNotSame($previousSuiteId, $suiteId);
            $previousSuiteId = $suiteId;
            ++$createdSuiteCount;
        }

        self::assertCount($createdSuiteCount, $labels);
    }

    public function testCreateSuiteNonUniqueLabelSameSourceDifferentTests(): void
    {
        $label = StringFactory::createRandom();

        $firstResponse = $this->applicationClient->makeCreateSuiteRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            [
                SuiteRequest::PARAMETER_SOURCE_ID => $this->sourceId,
                SuiteRequest::PARAMETER_LABEL => $label,
                SuiteRequest::PARAMETER_TESTS => [
                    'Test/test' . StringFactory::createRandom() . '.yaml',
                ],
            ]
        );

        self::assertSame(200, $firstResponse->getStatusCode());

        $secondResponse = $this->applicationClient->makeCreateSuiteRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            [
                SuiteRequest::PARAMETER_SOURCE_ID => $this->sourceId,
                SuiteRequest::PARAMETER_LABEL => $label,
                SuiteRequest::PARAMETER_TESTS => [
                    'Test/test' . StringFactory::createRandom() . '.yaml',
                ],
            ]
        );

        $expectedResponseData = [
            'class' => 'duplicate',
            'parameter' => [
                'name' => 'label',
                'value' => $label,
            ],
        ];

        self::assertJsonStringEqualsJsonString(
            (string) json_encode($expectedResponseData),
            $secondResponse->getBody()->getContents(),
        );
    }

    public function testCreateSuiteNonUniqueLabelDifferentSource(): void
    {
        $label = StringFactory::createRandom();

        $firstResponse = $this->applicationClient->makeCreateSuiteRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            [
                SuiteRequest::PARAMETER_SOURCE_ID => $this->createSource(self::USER_1_EMAIL),
                SuiteRequest::PARAMETER_LABEL => $label,
                SuiteRequest::PARAMETER_TESTS => [
                    'Test/test' . StringFactory::createRandom() . '.yaml',
                ],
            ]
        );

        self::assertSame(200, $firstResponse->getStatusCode());

        $secondResponse = $this->applicationClient->makeCreateSuiteRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            [
                SuiteRequest::PARAMETER_SOURCE_ID => $this->createSource(self::USER_1_EMAIL),
                SuiteRequest::PARAMETER_LABEL => $label,
                SuiteRequest::PARAMETER_TESTS => [
                    'Test/test' . StringFactory::createRandom() . '.yaml',
                ],
            ]
        );

        $expectedResponseData = [
            'class' => 'duplicate',
            'parameter' => [
                'name' => 'label',
                'value' => $label,
            ],
        ];

        self::assertJsonStringEqualsJsonString(
            (string) json_encode($expectedResponseData),
            $secondResponse->getBody()->getContents(),
        );
    }

    public function testCreateSuiteNonUniqueLabelDifferentUser(): void
    {
        $label = StringFactory::createRandom();

        $firstResponse = $this->applicationClient->makeCreateSuiteRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            [
                SuiteRequest::PARAMETER_SOURCE_ID => $this->createSource(self::USER_1_EMAIL),
                SuiteRequest::PARAMETER_LABEL => $label,
                SuiteRequest::PARAMETER_TESTS => [],
            ]
        );

        self::assertSame(200, $firstResponse->getStatusCode());

        $secondResponse = $this->applicationClient->makeCreateSuiteRequest(
            self::$apiTokens->get(self::USER_2_EMAIL),
            [
                SuiteRequest::PARAMETER_SOURCE_ID => $this->createSource(self::USER_2_EMAIL),
                SuiteRequest::PARAMETER_LABEL => $label,
                SuiteRequest::PARAMETER_TESTS => [],
            ]
        );

        self::assertSame(200, $secondResponse->getStatusCode());
    }

    public function testCreateSuiteWithLabelOfDeletedSuite(): void
    {
        $label = StringFactory::createRandom();

        $firstCreateResponse = $this->applicationClient->makeCreateSuiteRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            [
                SuiteRequest::PARAMETER_SOURCE_ID => $this->sourceId,
                SuiteRequest::PARAMETER_LABEL => $label,
                SuiteRequest::PARAMETER_TESTS => [
                    'Test/test' . StringFactory::createRandom() . '.yaml',
                ],
            ]
        );

        self::assertSame(200, $firstCreateResponse->getStatusCode());

        $createResponseData = json_decode($firstCreateResponse->getBody()->getContents(), true);
        \assert(is_array($createResponseData));
        $suiteId = $createResponseData['id'];

        $suiteRepository = self::getContainer()->get(SuiteRepository::class);
        \assert($suiteRepository instanceof SuiteRepository);
        $suite = $suiteRepository->find($suiteId);
        \assert($suite instanceof Suite);
        $suiteRepository->delete($suite);

        $secondCreateResponse = $this->applicationClient->makeCreateSuiteRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            [
                SuiteRequest::PARAMETER_SOURCE_ID => $this->sourceId,
                SuiteRequest::PARAMETER_LABEL => $label,
                SuiteRequest::PARAMETER_TESTS => [
                    'Test/test' . StringFactory::createRandom() . '.yaml',
                ],
            ]
        );

        self::assertSame(200, $secondCreateResponse->getStatusCode());
    }

    public function testCreateSuiteSourceNotFound(): void
    {
        $response = $this->applicationClient->makeCreateSuiteRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            [
                SuiteRequest::PARAMETER_LABEL => StringFactory::createRandom(),
                SuiteRequest::PARAMETER_TESTS => [],
            ],
        );

        self::assertSame(403, $response->getStatusCode());
    }
}
