<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Repository\SuiteRepository;
use App\Request\SuiteRequest;

abstract class AbstractUpdateSuiteTest extends AbstractSuiteTest
{
    public function testUpdateNewLabelNotUnique(): void
    {
        $suiteRepository = self::getContainer()->get(SuiteRepository::class);
        \assert($suiteRepository instanceof SuiteRepository);

        $suiteLabel1 = md5((string) rand());
        $suiteLabel2 = md5((string) rand());

        $suiteId = $this->createSuite($suiteLabel1, ['test.yaml']);
        $this->createSuite($suiteLabel2, ['test.yaml']);

        self::assertSame(1, $suiteRepository->count(['label' => $suiteLabel1]));

        $updateResponse = $this->applicationClient->makeUpdateSuiteRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            $suiteId,
            [
                SuiteRequest::PARAMETER_SOURCE_ID => $this->sourceId,
                SuiteRequest::PARAMETER_LABEL => $suiteLabel2,
                SuiteRequest::PARAMETER_TESTS => ['test.yaml'],
            ]
        );

        $this->responseAsserter->assertInvalidRequestJsonResponse(
            $updateResponse,
            [
                'error' => [
                    'type' => 'invalid_request',
                    'payload' => [
                        'name' => 'label',
                        'value' => $suiteLabel2,
                        'message' => 'This label is being used by another suite belonging to this user',
                    ],
                ],
            ]
        );
    }

    /**
     * @dataProvider updateSuccessDataProvider
     *
     * @param string[]              $initialSuiteTests
     * @param array<string, string> $updateRequestParameters
     */
    public function testUpdateSuccess(
        string $initialSuiteLabel,
        array $initialSuiteTests,
        array $updateRequestParameters
    ): void {
        $suiteId = $this->createSuite($initialSuiteLabel, $initialSuiteTests);

        $response = $this->applicationClient->makeUpdateSuiteRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            $suiteId,
            array_merge(['source_id' => $this->sourceId], $updateRequestParameters),
        );

        $expected = array_merge(
            $updateRequestParameters,
            [
                'id' => $suiteId,
                'source_id' => $this->sourceId,
            ]
        );

        $this->responseAsserter->assertSuccessfulJsonResponse($response, $expected);
    }

    /**
     * @return array<mixed>
     */
    public function updateSuccessDataProvider(): array
    {
        return [
            'label unchanged, tests change from empty to non-empty' => [
                'initialSuiteLabel' => 'label',
                'initialSuiteTests' => [],
                'updateRequestParameters' => [
                    SuiteRequest::PARAMETER_LABEL => 'label',
                    SuiteRequest::PARAMETER_TESTS => [
                        'Test/test' . md5((string) rand()) . '.yaml',
                        'Test/test' . md5((string) rand()) . '.yaml',
                    ],
                ],
            ],
            'label unchanged, tests change from non-empty to empty' => [
                'initialSuiteLabel' => 'label',
                'initialSuiteTests' => [
                    'Test/test' . md5((string) rand()) . '.yaml',
                ],
                'updateRequestParameters' => [
                    SuiteRequest::PARAMETER_LABEL => 'label',
                    SuiteRequest::PARAMETER_TESTS => [],
                ],
            ],
            'label changed, tests remain same and empty' => [
                'initialSuiteLabel' => 'label',
                'initialSuiteTests' => [],
                'updateRequestParameters' => [
                    SuiteRequest::PARAMETER_LABEL => 'new label',
                    SuiteRequest::PARAMETER_TESTS => [],
                ],
            ],
            'label changed, tests remain same and non-empty' => [
                'initialSuiteLabel' => 'label',
                'initialSuiteTests' => [
                    'Test/test1.yaml',
                    'Test/test2.yaml',
                ],
                'updateRequestParameters' => [
                    SuiteRequest::PARAMETER_LABEL => 'new label',
                    SuiteRequest::PARAMETER_TESTS => [
                        'Test/test1.yaml',
                        'Test/test2.yaml',
                    ],
                ],
            ],
        ];
    }

    public function testUpdateIsIdempotent(): void
    {
        $initialLabel = md5((string) rand());
        $initialTests = ['Test/test' . md5((string) rand()) . '.yaml'];

        $newLabel = md5((string) rand());
        $newTests = ['Test/test' . md5((string) rand()) . '.yaml'];

        $suiteId = $this->createSuite($initialLabel, $initialTests);

        $updateParameters = [
            SuiteRequest::PARAMETER_SOURCE_ID => $this->sourceId,
            SuiteRequest::PARAMETER_LABEL => $newLabel,
            SuiteRequest::PARAMETER_TESTS => $newTests,
        ];

        $firstUpdateResponse = $this->applicationClient->makeUpdateSuiteRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            $suiteId,
            $updateParameters,
        );

        self::assertSame(200, $firstUpdateResponse->getStatusCode());

        $secondUpdateResponse = $this->applicationClient->makeUpdateSuiteRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            $suiteId,
            $updateParameters,
        );

        self::assertSame(200, $secondUpdateResponse->getStatusCode());
        self::assertSame(
            $firstUpdateResponse->getBody()->getContents(),
            $secondUpdateResponse->getBody()->getContents()
        );
    }

    /**
     * @param string[] $tests
     */
    private function createSuite(string $label, array $tests): string
    {
        $response = $this->applicationClient->makeCreateSuiteRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            [
                SuiteRequest::PARAMETER_SOURCE_ID => $this->sourceId,
                SuiteRequest::PARAMETER_LABEL => $label,
                SuiteRequest::PARAMETER_TESTS => $tests,
            ]
        );

        $responseData = json_decode($response->getBody()->getContents(), true);
        \assert(is_array($responseData));
        $sourceId = $responseData['id'] ?? null;
        \assert(is_string($sourceId));

        return $sourceId;
    }
}
