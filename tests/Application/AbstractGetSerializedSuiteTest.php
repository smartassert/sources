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
use SmartAssert\TestAuthenticationProviderBundle\UserProvider;

abstract class AbstractGetSerializedSuiteTest extends AbstractApplicationTest
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
     * @param callable(UserProvider): SourceInterface $sourceCreator
     * @param callable(SourceInterface): Suite        $suiteCreator
     * @param callable(Suite): SerializedSuite        $serializedSuiteCreator
     * @param array<string, string>                   $payload
     * @param callable(SerializedSuite): array<mixed> $expectedResponseDataCreator
     */
    public function testGetSuccess(
        callable $sourceCreator,
        callable $suiteCreator,
        callable $serializedSuiteCreator,
        array $payload,
        callable $expectedResponseDataCreator,
    ): void {
        $source = $sourceCreator(self::$users);
        $this->sourceRepository->save($source);

        $suite = $suiteCreator($source);
        $this->suiteRepository->save($suite);

        $serializedSuite = $serializedSuiteCreator($suite);
        $this->serializedSuiteRepository->save($serializedSuite);

        $response = $this->applicationClient->makeGetSerializedSuiteRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $serializedSuite->id,
        );

        $this->responseAsserter->assertSuccessfulJsonResponse(
            $response,
            $expectedResponseDataCreator($serializedSuite)
        );
    }

    /**
     * @return array<mixed>
     */
    public static function serializeSuccessDataProvider(): array
    {
        return [
            'no parameters, state=requested' => [
                'sourceCreator' => function (UserProvider $users) {
                    return SourceOriginFactory::create(
                        type: 'file',
                        userId: $users->get(self::USER_1_EMAIL)->id,
                    );
                },
                'suiteCreator' => function (SourceInterface $source) {
                    return SuiteFactory::create(source: $source, tests: []);
                },
                'serializedSuiteCreator' => function (Suite $suite) {
                    return new SerializedSuite(
                        (new EntityIdFactory())->create(),
                        $suite,
                        []
                    );
                },
                'payload' => [],
                'expectedResponseDataCreator' => function (SerializedSuite $serializedSuite) {
                    return [
                        'id' => $serializedSuite->id,
                        'suite_id' => $serializedSuite->suite->id,
                        'parameters' => [],
                        'state' => State::REQUESTED->value,
                    ];
                },
            ],
            'has parameters, state=requested' => [
                'sourceCreator' => function (UserProvider $users) {
                    return SourceOriginFactory::create(
                        type: 'file',
                        userId: $users->get(self::USER_1_EMAIL)->id,
                    );
                },
                'suiteCreator' => function (SourceInterface $source) {
                    return SuiteFactory::create(source: $source, tests: []);
                },
                'serializedSuiteCreator' => function (Suite $suite) {
                    return new SerializedSuite(
                        (new EntityIdFactory())->create(),
                        $suite,
                        [
                            'key1' => 'value1',
                            'key2' => 'value2',
                        ]
                    );
                },
                'payload' => [],
                'expectedResponseDataCreator' => function (SerializedSuite $serializedSuite) {
                    return [
                        'id' => $serializedSuite->id,
                        'suite_id' => $serializedSuite->suite->id,
                        'parameters' => [
                            'key1' => 'value1',
                            'key2' => 'value2',
                        ],
                        'state' => State::REQUESTED->value,
                    ];
                },
            ],
            'no parameters, state=prepared' => [
                'sourceCreator' => function (UserProvider $users) {
                    return SourceOriginFactory::create(
                        type: 'file',
                        userId: $users->get(self::USER_1_EMAIL)->id,
                    );
                },
                'suiteCreator' => function (SourceInterface $source) {
                    return SuiteFactory::create(source: $source, tests: []);
                },
                'serializedSuiteCreator' => function (Suite $suite) {
                    $serializedSuite = new SerializedSuite(
                        (new EntityIdFactory())->create(),
                        $suite,
                        []
                    );

                    $serializedSuite->setState(State::PREPARED);

                    return $serializedSuite;
                },
                'payload' => [],
                'expectedResponseDataCreator' => function (SerializedSuite $serializedSuite) {
                    return [
                        'id' => $serializedSuite->id,
                        'suite_id' => $serializedSuite->suite->id,
                        'parameters' => [],
                        'state' => State::PREPARED->value,
                    ];
                },
            ],
        ];
    }
}
