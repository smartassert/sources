<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\Suite;
use App\Repository\SuiteRepository;
use App\Request\SuiteRequest;

abstract class AbstractCreateSuiteTest extends AbstractSuiteTest
{
    /**
     * @dataProvider createSourceSuccessDataProvider
     *
     * @param array<string, string> $requestParameters
     */
    public function testCreateSuccess(array $requestParameters): void
    {
        $response = $this->applicationClient->makeCreateSuiteRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            $this->sourceId,
            $requestParameters
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
            self::$authenticationConfiguration->getUser(self::USER_1_EMAIL)->id,
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
    public function createSourceSuccessDataProvider(): array
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

    public function testCreateIsIdempotent(): void
    {
        $requestParameters = [
            SuiteRequest::PARAMETER_LABEL => 'label',
            SuiteRequest::PARAMETER_TESTS => [
                'Test/test' . md5((string) rand()) . '.yaml',
            ],
        ];

        $firstResponse = $this->applicationClient->makeCreateSuiteRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            $this->sourceId,
            $requestParameters
        );

        self::assertSame(200, $firstResponse->getStatusCode());

        $secondResponse = $this->applicationClient->makeCreateSuiteRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            $this->sourceId,
            $requestParameters
        );

        self::assertSame(200, $secondResponse->getStatusCode());
        self::assertSame($firstResponse->getBody()->getContents(), $secondResponse->getBody()->getContents());
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
                self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
                $this->sourceId,
                [
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
}