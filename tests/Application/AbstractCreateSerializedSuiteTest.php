<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\SerializedSuite;
use App\Entity\SourceOriginInterface;
use App\Entity\Suite;
use App\Enum\RunSource\State;
use App\Repository\SerializedSuiteRepository;
use App\Repository\SourceRepository;
use App\Repository\SuiteRepository;
use App\Tests\Services\AuthenticationConfiguration;
use App\Tests\Services\SourceOriginFactory;
use App\Tests\Services\SuiteFactory;

abstract class AbstractCreateSerializedSuiteTest extends AbstractApplicationTest
{
    private SourceRepository $sourceRepository;
    private SerializedSuiteRepository $serializedSuiteRepository;
    private SuiteRepository $suiteRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $sourceRepository = self::getContainer()->get(SourceRepository::class);
        \assert($sourceRepository instanceof SourceRepository);
        $this->sourceRepository = $sourceRepository;

        $serializedSuiteRepository = self::getContainer()->get(SerializedSuiteRepository::class);
        \assert($serializedSuiteRepository instanceof SerializedSuiteRepository);
        $this->serializedSuiteRepository = $serializedSuiteRepository;

        $suiteRepository = self::getContainer()->get(SuiteRepository::class);
        \assert($suiteRepository instanceof SuiteRepository);
        $this->suiteRepository = $suiteRepository;
    }

    /**
     * @dataProvider serializeSuccessDataProvider
     *
     * @param callable(AuthenticationConfiguration): SourceOriginInterface $sourceCreator
     * @param callable(SourceOriginInterface): Suite                       $suiteCreator
     * @param array<string, string>                                        $payload
     * @param array<string, string>                                        $expectedResponseParameters
     */
    public function testSerializeSuccess(
        callable $sourceCreator,
        callable $suiteCreator,
        array $payload,
        array $expectedResponseParameters,
    ): void {
        $source = $sourceCreator(self::$authenticationConfiguration);
        $this->sourceRepository->save($source);

        $suite = $suiteCreator($source);
        $this->suiteRepository->save($suite);

        self::assertEquals(0, $this->serializedSuiteRepository->count(['suite' => $suite]));

        $response = $this->applicationClient->makeCreateSerializedSuiteRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            $suite->id,
            $payload
        );

        $serializedSuite = $this->serializedSuiteRepository->findOneBy(['suite' => $suite]);
        self::assertInstanceOf(SerializedSuite::class, $serializedSuite);

        $this->responseAsserter->assertSerializeSuiteSuccessResponse(
            $response,
            [
                'id' => $serializedSuite->id,
                'suite_id' => $suite->id,
                'parameters' => $expectedResponseParameters,
                'state' => State::REQUESTED->value,
            ]
        );
    }

    /**
     * @return array<mixed>
     */
    public function serializeSuccessDataProvider(): array
    {
        return [
            'file, empty tests' => [
                'sourceCreator' => function (AuthenticationConfiguration $authenticationConfiguration) {
                    return SourceOriginFactory::create(
                        type: 'file',
                        userId: $authenticationConfiguration->getUser(self::USER_1_EMAIL)->id,
                    );
                },
                'suiteCreator' => function (SourceOriginInterface $source) {
                    return SuiteFactory::create(source: $source, tests: []);
                },
                'payload' => [],
                'expectedResponseParameters' => [],
            ],
            'file, non-empty tests' => [
                'sourceCreator' => function (AuthenticationConfiguration $authenticationConfiguration) {
                    return SourceOriginFactory::create(
                        type: 'file',
                        userId: $authenticationConfiguration->getUser(self::USER_1_EMAIL)->id,
                    );
                },
                'suiteCreator' => function (SourceOriginInterface $source) {
                    return SuiteFactory::create(source: $source, tests: ['test.yaml']);
                },
                'payload' => [],
                'expectedResponseParameters' => [],
            ],
            'git' => [
                'sourceCreator' => function (AuthenticationConfiguration $authenticationConfiguration) {
                    return SourceOriginFactory::create(
                        type: 'git',
                        userId: $authenticationConfiguration->getUser(self::USER_1_EMAIL)->id,
                    );
                },
                'suiteCreator' => function (SourceOriginInterface $source) {
                    return SuiteFactory::create(source: $source, tests: ['test.yaml']);
                },
                'payload' => [],
                'expectedResponseParameters' => [],
            ],
            'git with ref request parameters' => [
                'sourceCreator' => function (AuthenticationConfiguration $authenticationConfiguration) {
                    return SourceOriginFactory::create(
                        type: 'git',
                        userId: $authenticationConfiguration->getUser(self::USER_1_EMAIL)->id,
                    );
                },
                'suiteCreator' => function (SourceOriginInterface $source) {
                    return SuiteFactory::create(source: $source, tests: ['test.yaml']);
                },
                'payload' => [
                    'ref' => 'v1.1',
                ],
                'expectedResponseParameters' => [
                    'ref' => 'v1.1',
                ],
            ],
            'git with request parameters including ref' => [
                'sourceCreator' => function (AuthenticationConfiguration $authenticationConfiguration) {
                    return SourceOriginFactory::create(
                        type: 'git',
                        userId: $authenticationConfiguration->getUser(self::USER_1_EMAIL)->id,
                    );
                },
                'suiteCreator' => function (SourceOriginInterface $source) {
                    return SuiteFactory::create(source: $source, tests: ['test.yaml']);
                },
                'payload' => [
                    'ref' => 'v1.1',
                    'ignored1' => 'value',
                    'ignored2' => 'value',
                ],
                'expectedResponseParameters' => [
                    'ref' => 'v1.1',
                ],
            ],
        ];
    }

    public function testSerializeIsNotIdempotent(): void
    {
        $source = SourceOriginFactory::create(
            type: 'file',
            userId: self::$authenticationConfiguration->getUser(self::USER_1_EMAIL)->id,
        );
        $this->sourceRepository->save($source);

        $suite = SuiteFactory::create(source: $source, tests: []);
        $this->suiteRepository->save($suite);

        self::assertEquals(0, $this->serializedSuiteRepository->count(['suite' => $suite]));

        $firstResponse = $this->applicationClient->makeCreateSerializedSuiteRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            $suite->id,
            []
        );

        $firstResponseData = json_decode($firstResponse->getBody()->getContents(), true);
        self::assertIsArray($firstResponseData);

        $secondResponse = $this->applicationClient->makeCreateSerializedSuiteRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            $suite->id,
            []
        );

        $secondResponseData = json_decode($secondResponse->getBody()->getContents(), true);
        self::assertIsArray($secondResponseData);

        self::assertNotSame($firstResponseData['id'], $secondResponseData['id']);
        self::assertSame($firstResponseData['suite_id'], $secondResponseData['suite_id']);
        self::assertSame($firstResponseData['parameters'], $secondResponseData['parameters']);
        self::assertSame($firstResponseData['state'], $secondResponseData['state']);
    }
}