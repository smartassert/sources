<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\Suite;
use App\Repository\SuiteRepository;
use App\Request\SuiteRequest;
use App\Tests\DataProvider\CreateUpdateSuiteDataProviderTrait;

abstract class AbstractCreateSuiteTest extends AbstractSuiteTest
{
    use CreateUpdateSuiteDataProviderTrait;

    /**
     * @dataProvider createUpdateSuiteInvalidRequestDataProvider
     *
     * @param array<mixed> $requestParameters
     * @param array<mixed> $expectedResponseData
     */
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
     * @dataProvider createSuccessDataProvider
     *
     * @param array<string, string> $requestParameters
     */
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

        $this->responseAsserter->assertSuccessfulJsonResponse($response, $expected);
    }

    /**
     * @return array<mixed>
     */
    public static function createSuccessDataProvider(): array
    {
        return [
            'no tests' => [
                'requestParameters' => [
                    SuiteRequest::PARAMETER_LABEL => md5((string) rand()),
                    SuiteRequest::PARAMETER_TESTS => [],
                ],
            ],
            'has tests' => [
                'requestParameters' => [
                    SuiteRequest::PARAMETER_LABEL => md5((string) rand()),
                    SuiteRequest::PARAMETER_TESTS => [
                        'Test/test' . md5((string) rand()) . '.yaml',
                        'Test/test' . md5((string) rand()) . '.yaml',
                        'Test/test' . md5((string) rand()) . '.yaml',
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider createIsIdempotentDataProvider
     *
     * @param array<mixed> $requestParameters
     */
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
                        'Test/test' . md5((string) rand()) . '.yaml',
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
            md5((string) rand()),
            md5((string) rand()),
            md5((string) rand()),
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
                        'Test/test' . md5((string) rand()) . '.yaml',
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
        $label = md5((string) rand());

        $firstResponse = $this->applicationClient->makeCreateSuiteRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            [
                SuiteRequest::PARAMETER_SOURCE_ID => $this->sourceId,
                SuiteRequest::PARAMETER_LABEL => $label,
                SuiteRequest::PARAMETER_TESTS => [
                    'Test/test' . md5((string) rand()) . '.yaml',
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
                    'Test/test' . md5((string) rand()) . '.yaml',
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
        $label = md5((string) rand());

        $firstResponse = $this->applicationClient->makeCreateSuiteRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            [
                SuiteRequest::PARAMETER_SOURCE_ID => $this->createSource(self::USER_1_EMAIL),
                SuiteRequest::PARAMETER_LABEL => $label,
                SuiteRequest::PARAMETER_TESTS => [
                    'Test/test' . md5((string) rand()) . '.yaml',
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
                    'Test/test' . md5((string) rand()) . '.yaml',
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
        $label = md5((string) rand());

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
        $label = md5((string) rand());

        $firstCreateResponse = $this->applicationClient->makeCreateSuiteRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            [
                SuiteRequest::PARAMETER_SOURCE_ID => $this->sourceId,
                SuiteRequest::PARAMETER_LABEL => $label,
                SuiteRequest::PARAMETER_TESTS => [
                    'Test/test' . md5((string) rand()) . '.yaml',
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
                    'Test/test' . md5((string) rand()) . '.yaml',
                ],
            ]
        );

        self::assertSame(200, $secondCreateResponse->getStatusCode());
    }
}
