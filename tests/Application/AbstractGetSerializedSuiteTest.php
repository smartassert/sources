<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\SerializedSuite;
use App\Entity\SourceInterface;
use App\Entity\Suite;
use App\Enum\SerializedSuite\FailureReason;
use App\Enum\SerializedSuite\State;
use App\Repository\SerializedSuiteRepository;
use App\Repository\SourceRepository;
use App\Repository\SuiteRepository;
use App\Services\EntityIdFactory;
use App\Tests\Services\SourceOriginFactory;
use App\Tests\Services\SuiteFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use SmartAssert\TestAuthenticationProviderBundle\UserProvider;

abstract class AbstractGetSerializedSuiteTest extends AbstractApplicationTest
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
     * @param callable(Suite): SerializedSuite        $serializedSuiteCreator
     * @param callable(SerializedSuite): array<mixed> $expectedResponseDataCreator
     */
    #[DataProvider('serializeSuccessDataProvider')]
    public function testGetSuccess(
        callable $sourceCreator,
        callable $suiteCreator,
        callable $serializedSuiteCreator,
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
            $serializedSuite->getId(),
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/json', $response->getHeaderLine('content-type'));
        self::assertJsonStringEqualsJsonString(
            (string) json_encode($expectedResponseDataCreator($serializedSuite)),
            $response->getBody()->getContents()
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
                        userId: $users->get(self::USER_1_EMAIL)['id'],
                    );
                },
                'suiteCreator' => function (SourceInterface $source) {
                    return SuiteFactory::create(source: $source, tests: []);
                },
                'serializedSuiteCreator' => function (Suite $suite) {
                    return new SerializedSuite(
                        (new EntityIdFactory())->create(),
                        $suite,
                        'https://example.com/nofity',
                        []
                    );
                },
                'expectedResponseDataCreator' => function (SerializedSuite $serializedSuite) {
                    return [
                        'id' => $serializedSuite->getId(),
                        'suite_id' => $serializedSuite->getSuite()->getId(),
                        'parameters' => [],
                        'state' => State::REQUESTED->value,
                        'is_prepared' => false,
                        'has_end_state' => false,
                        'meta_state' => [
                            'pending' => true,
                            'ended' => false,
                            'succeeded' => false,
                        ],
                        'previous_states' => [],
                        'next_states' => [
                            State::PREPARING_RUNNING->value,
                            State::PREPARING_HALTED->value,
                        ],
                    ];
                },
            ],
            'has parameters, state=requested' => [
                'sourceCreator' => function (UserProvider $users) {
                    return SourceOriginFactory::create(
                        type: 'file',
                        userId: $users->get(self::USER_1_EMAIL)['id'],
                    );
                },
                'suiteCreator' => function (SourceInterface $source) {
                    return SuiteFactory::create(source: $source, tests: []);
                },
                'serializedSuiteCreator' => function (Suite $suite) {
                    return new SerializedSuite(
                        (new EntityIdFactory())->create(),
                        $suite,
                        'https://example.com/nofity',
                        [
                            'key1' => 'value1',
                            'key2' => 'value2',
                        ]
                    );
                },
                'expectedResponseDataCreator' => function (SerializedSuite $serializedSuite) {
                    return [
                        'id' => $serializedSuite->getId(),
                        'suite_id' => $serializedSuite->getSuite()->getId(),
                        'parameters' => [
                            'key1' => 'value1',
                            'key2' => 'value2',
                        ],
                        'state' => State::REQUESTED->value,
                        'is_prepared' => false,
                        'has_end_state' => false,
                        'meta_state' => [
                            'pending' => true,
                            'ended' => false,
                            'succeeded' => false,
                        ],
                        'previous_states' => [],
                        'next_states' => [
                            State::PREPARING_RUNNING->value,
                            State::PREPARING_HALTED->value,
                        ],
                    ];
                },
            ],
            'no parameters, state=prepared' => [
                'sourceCreator' => function (UserProvider $users) {
                    return SourceOriginFactory::create(
                        type: 'file',
                        userId: $users->get(self::USER_1_EMAIL)['id'],
                    );
                },
                'suiteCreator' => function (SourceInterface $source) {
                    return SuiteFactory::create(source: $source, tests: []);
                },
                'serializedSuiteCreator' => function (Suite $suite) {
                    $serializedSuite = new SerializedSuite(
                        (new EntityIdFactory())->create(),
                        $suite,
                        'https://example.com/nofity',
                        []
                    );

                    $serializedSuite->setState(State::PREPARED);

                    return $serializedSuite;
                },
                'expectedResponseDataCreator' => function (SerializedSuite $serializedSuite) {
                    return [
                        'id' => $serializedSuite->getId(),
                        'suite_id' => $serializedSuite->getSuite()->getId(),
                        'parameters' => [],
                        'state' => State::PREPARED->value,
                        'is_prepared' => true,
                        'has_end_state' => true,
                        'meta_state' => [
                            'pending' => false,
                            'ended' => true,
                            'succeeded' => true,
                        ],
                        'previous_states' => [
                            State::REQUESTED->value,
                            State::PREPARING_RUNNING->value,
                            State::PREPARING_HALTED->value,
                        ],
                        'next_states' => [],
                    ];
                },
            ],
            'no parameters, state=failed' => [
                'sourceCreator' => function (UserProvider $users) {
                    return SourceOriginFactory::create(
                        type: 'file',
                        userId: $users->get(self::USER_1_EMAIL)['id'],
                    );
                },
                'suiteCreator' => function (SourceInterface $source) {
                    return SuiteFactory::create(source: $source, tests: []);
                },
                'serializedSuiteCreator' => function (Suite $suite) {
                    $serializedSuite = new SerializedSuite(
                        (new EntityIdFactory())->create(),
                        $suite,
                        'https://example.com/nofity',
                        []
                    );

                    $serializedSuite->setPreparationFailed(FailureReason::GIT_CHECKOUT, 'repository does not exist');

                    return $serializedSuite;
                },
                'expectedResponseDataCreator' => function (SerializedSuite $serializedSuite) {
                    return [
                        'id' => $serializedSuite->getId(),
                        'suite_id' => $serializedSuite->getSuite()->getId(),
                        'parameters' => [],
                        'state' => State::FAILED->value,
                        'is_prepared' => false,
                        'has_end_state' => true,
                        'failure_reason' => 'git/checkout',
                        'failure_message' => 'repository does not exist',
                        'meta_state' => [
                            'pending' => false,
                            'ended' => true,
                            'succeeded' => false,
                        ],
                        'previous_states' => [
                            State::REQUESTED->value,
                            State::PREPARING_RUNNING->value,
                            State::PREPARING_HALTED->value,
                        ],
                        'next_states' => [],
                    ];
                },
            ],
        ];
    }

    public function testGetSerializedSuiteNotFound(): void
    {
        $response = $this->applicationClient->makeGetSerializedSuiteRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            (new EntityIdFactory())->create(),
        );

        self::assertSame(403, $response->getStatusCode());
    }
}
