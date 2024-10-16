<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\SerializedSuite;
use App\Entity\SourceInterface;
use App\Entity\Suite;
use App\Enum\SerializedSuite\State;
use App\Repository\SerializedSuiteRepository;
use App\Repository\SourceRepository;
use App\Repository\SuiteRepository;
use App\Services\EntityIdFactory;
use App\Tests\Services\SourceOriginFactory;
use App\Tests\Services\SuiteFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use SmartAssert\TestAuthenticationProviderBundle\UserProvider;

abstract class AbstractCreateSerializedSuiteTest extends AbstractApplicationTest
{
    private SourceRepository $sourceRepository;
    private SerializedSuiteRepository $serializedSuiteRepository;
    private SuiteRepository $suiteRepository;

    #[\Override]
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
     * @param callable(UserProvider): SourceInterface $sourceCreator
     * @param callable(SourceInterface): Suite        $suiteCreator
     * @param array<string, string>                   $payload
     * @param array<string, string>                   $expectedResponseParameters
     */
    #[DataProvider('serializeSuccessDataProvider')]
    public function testSerializeSuccess(
        callable $sourceCreator,
        callable $suiteCreator,
        array $payload,
        array $expectedResponseParameters,
    ): void {
        $serializedSuiteId = (new EntityIdFactory())->create();
        $source = $sourceCreator(self::$users);
        $this->sourceRepository->save($source);

        $suite = $suiteCreator($source);
        $this->suiteRepository->save($suite);

        self::assertEquals(0, $this->serializedSuiteRepository->count(['suite' => $suite]));

        $response = $this->applicationClient->makeCreateSerializedSuiteRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $serializedSuiteId,
            $suite->id,
            $payload
        );

        $serializedSuite = $this->serializedSuiteRepository->findOneBy(['suite' => $suite]);
        self::assertInstanceOf(SerializedSuite::class, $serializedSuite);

        self::assertSame(202, $response->getStatusCode());
        self::assertSame('application/json', $response->getHeaderLine('content-type'));
        self::assertJsonStringEqualsJsonString(
            (string) json_encode([
                'id' => $serializedSuiteId,
                'suite_id' => $suite->id,
                'parameters' => $expectedResponseParameters,
                'state' => State::REQUESTED->value,
                'is_prepared' => false,
                'has_end_state' => false,
            ]),
            $response->getBody()->getContents()
        );
    }

    /**
     * @return array<mixed>
     */
    public static function serializeSuccessDataProvider(): array
    {
        return [
            'file, empty tests' => [
                'sourceCreator' => function (UserProvider $users) {
                    return SourceOriginFactory::create(
                        type: 'file',
                        userId: $users->get(self::USER_1_EMAIL)['id'],
                    );
                },
                'suiteCreator' => function (SourceInterface $source) {
                    return SuiteFactory::create(source: $source, tests: []);
                },
                'payload' => [],
                'expectedResponseParameters' => [],
            ],
            'file, non-empty tests' => [
                'sourceCreator' => function (UserProvider $users) {
                    return SourceOriginFactory::create(
                        type: 'file',
                        userId: $users->get(self::USER_1_EMAIL)['id'],
                    );
                },
                'suiteCreator' => function (SourceInterface $source) {
                    return SuiteFactory::create(source: $source, tests: ['test.yaml']);
                },
                'payload' => [],
                'expectedResponseParameters' => [],
            ],
            'git' => [
                'sourceCreator' => function (UserProvider $users) {
                    return SourceOriginFactory::create(
                        type: 'git',
                        userId: $users->get(self::USER_1_EMAIL)['id'],
                    );
                },
                'suiteCreator' => function (SourceInterface $source) {
                    return SuiteFactory::create(source: $source, tests: ['test.yaml']);
                },
                'payload' => [],
                'expectedResponseParameters' => [],
            ],
            'git with ref request parameters' => [
                'sourceCreator' => function (UserProvider $users) {
                    return SourceOriginFactory::create(
                        type: 'git',
                        userId: $users->get(self::USER_1_EMAIL)['id'],
                    );
                },
                'suiteCreator' => function (SourceInterface $source) {
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
                'sourceCreator' => function (UserProvider $users) {
                    return SourceOriginFactory::create(
                        type: 'git',
                        userId: $users->get(self::USER_1_EMAIL)['id'],
                    );
                },
                'suiteCreator' => function (SourceInterface $source) {
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

    public function testSerializeIsIdempotent(): void
    {
        $serializedSuiteId = (new EntityIdFactory())->create();

        $source = SourceOriginFactory::create(
            type: 'file',
            userId: self::$users->get(self::USER_1_EMAIL)['id'],
        );
        $this->sourceRepository->save($source);

        $suite = SuiteFactory::create(source: $source, tests: []);
        $this->suiteRepository->save($suite);

        self::assertEquals(0, $this->serializedSuiteRepository->count(['suite' => $suite]));

        $firstResponse = $this->applicationClient->makeCreateSerializedSuiteRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $serializedSuiteId,
            $suite->id,
            []
        );

        $firstResponseData = json_decode($firstResponse->getBody()->getContents(), true);
        self::assertIsArray($firstResponseData);

        $secondResponse = $this->applicationClient->makeCreateSerializedSuiteRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $serializedSuiteId,
            $suite->id,
            []
        );

        $secondResponseData = json_decode($secondResponse->getBody()->getContents(), true);
        self::assertIsArray($secondResponseData);

        self::assertSame($firstResponseData['id'], $secondResponseData['id']);
        self::assertSame($firstResponseData['suite_id'], $secondResponseData['suite_id']);
        self::assertSame($firstResponseData['parameters'], $secondResponseData['parameters']);
    }

    public function testSerializeSuiteNotFound(): void
    {
        $serializedSuiteId = (new EntityIdFactory())->create();

        $source = SourceOriginFactory::create(
            type: 'file',
            userId: self::$users->get(self::USER_1_EMAIL)['id'],
        );
        $this->sourceRepository->save($source);

        $suite = SuiteFactory::create(source: $source, tests: []);
        $this->suiteRepository->save($suite);

        self::assertEquals(0, $this->serializedSuiteRepository->count(['suite' => $suite]));

        $response = $this->applicationClient->makeCreateSerializedSuiteRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $serializedSuiteId,
            (new EntityIdFactory())->create(),
            []
        );

        self::assertSame(403, $response->getStatusCode());
    }
}
