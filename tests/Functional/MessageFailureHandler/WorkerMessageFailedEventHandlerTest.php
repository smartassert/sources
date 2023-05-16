<?php

declare(strict_types=1);

namespace App\Tests\Functional\MessageFailureHandler;

use App\Entity\GitSource;
use App\Entity\SerializedSuite;
use App\Entity\Suite;
use App\Enum\SerializedSuite\State;
use App\Exception\GitActionException;
use App\Exception\GitRepositoryException;
use App\Exception\MessageHandler\SuiteSerializationException;
use App\Exception\NoSourceRepositoryCreatorException;
use App\Exception\SourceRepositoryCreationException;
use App\Exception\SourceRepositoryReaderNotFoundException;
use App\Exception\UnableToWriteSerializedSuiteException;
use App\Message\SerializeSuite;
use App\MessageFailureHandler\WorkerMessageFailedEventHandler;
use App\Model\SourceRepositoryInterface;
use App\Repository\SerializedSuiteRepository;
use App\Repository\SourceRepository;
use App\Repository\SuiteRepository;
use App\Tests\Services\EntityRemover;
use League\Flysystem\PathTraversalDetected;
use League\Flysystem\UnableToWriteFile;
use SmartAssert\YamlFile\Exception\Collection\SerializeException;
use SmartAssert\YamlFile\Exception\ProvisionException;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Exception\HandlerFailedException;

class WorkerMessageFailedEventHandlerTest extends WebTestCase
{
    private WorkerMessageFailedEventHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $handler = self::getContainer()->get(WorkerMessageFailedEventHandler::class);
        \assert($handler instanceof WorkerMessageFailedEventHandler);
        $this->handler = $handler;

        $entityRemover = self::getContainer()->get(EntityRemover::class);
        if ($entityRemover instanceof EntityRemover) {
            $entityRemover->removeAll();
        }
    }

    /**
     * @dataProvider handleDoesNotHandleDataProvider
     */
    public function testHandleDoesNotHandle(WorkerMessageFailedEvent $event, int $expectedReturnState): void
    {
        self::assertSame($expectedReturnState, $this->handler->handle($event));
    }

    /**
     * @return array<mixed>
     */
    public function handleDoesNotHandleDataProvider(): array
    {
        return [
            'event will retry' => [
                'event' => (function () {
                    $event = new WorkerMessageFailedEvent(
                        new Envelope(new SerializeSuite(md5((string) rand()), [])),
                        'async',
                        \Mockery::mock(HandlerFailedException::class),
                    );
                    $event->setForRetry();

                    return $event;
                })(),
                'expectedReturnState' => WorkerMessageFailedEventHandler::STATE_EVENT_WILL_RETRY,
            ],
            'incorrect exception type' => [
                'event' => new WorkerMessageFailedEvent(
                    new Envelope(new SerializeSuite(md5((string) rand()), [])),
                    'async',
                    new \Exception()
                ),
                'expectedReturnState' => WorkerMessageFailedEventHandler::STATE_EVENT_EXCEPTION_INCORRECT_TYPE,
            ],
        ];
    }

    /**
     * @dataProvider handleNoSerializedSuiteStateChangeDataProvider
     *
     * @param callable(SerializedSuite): \Exception[] $handlerFailedExceptionNestedExceptionsCreator
     */
    public function testHandleNoSerializedSuiteStateChange(
        callable $handlerFailedExceptionNestedExceptionsCreator
    ): void {
        $serializedSuite = $this->createSerializedSuite();
        self::assertSame(State::REQUESTED, $serializedSuite->getState());

        $returnState = $this->handleEvent($serializedSuite, $handlerFailedExceptionNestedExceptionsCreator);

        self::assertSame(WorkerMessageFailedEventHandler::STATE_SUCCESS, $returnState);
        self::assertSame(State::REQUESTED->value, $serializedSuite->getState()->value);
    }

    /**
     * @return array<mixed>
     */
    public function handleNoSerializedSuiteStateChangeDataProvider(): array
    {
        return [
            'no relevant nested exceptions' => [
                'handlerFailedExceptionNestedExceptionsCreator' => function () {
                    return [new \Exception()];
                },
            ],
        ];
    }

    /**
     * @dataProvider handleHasSerializedSuiteStateChangeDataProvider
     *
     * @param callable(SerializedSuite): \Exception[] $handlerFailedExceptionNestedExceptionsCreator
     */
    public function testHandleHasSerializedSuiteStateChange(
        callable $handlerFailedExceptionNestedExceptionsCreator,
        string $expectedFailureReason,
        string $expectedFailureMessage,
    ): void {
        $serializedSuite = $this->createSerializedSuite();
        self::assertSame(State::REQUESTED->value, $serializedSuite->getState()->value);

        $returnState = $this->handleEvent($serializedSuite, $handlerFailedExceptionNestedExceptionsCreator);
        self::assertSame(WorkerMessageFailedEventHandler::STATE_SUCCESS, $returnState);
        self::assertSame(State::FAILED->value, $serializedSuite->getState()->value);

        $serializedSuiteData = $serializedSuite->jsonSerialize();
        self::assertArrayHasKey('failure_reason', $serializedSuiteData);
        self::assertArrayHasKey('failure_message', $serializedSuiteData);
        self::assertSame($expectedFailureReason, $serializedSuiteData['failure_reason']);
        self::assertSame($expectedFailureMessage, $serializedSuiteData['failure_message']);
    }

    /**
     * @return array<mixed>
     */
    public function handleHasSerializedSuiteStateChangeDataProvider(): array
    {
        return [
            'git clone failure' => [
                'handlerFailedExceptionNestedExceptionsCreator' => function (SerializedSuite $serializedSuite) {
                    return [
                        new SuiteSerializationException(
                            $serializedSuite,
                            new SourceRepositoryCreationException(
                                new GitRepositoryException(
                                    new GitActionException(
                                        GitActionException::ACTION_CLONE,
                                        'fatal: repository \'https://example.com/repository.git/\' not found'
                                    ),
                                ),
                            )
                        ),
                    ];
                },
                'expectedFailureReason' => 'git/clone',
                'expectedFailureMessage' => 'fatal: repository \'https://example.com/repository.git/\' not found',
            ],
            'git checkout failure' => [
                'handlerFailedExceptionNestedExceptionsCreator' => function (SerializedSuite $serializedSuite) {
                    return [
                        new SuiteSerializationException(
                            $serializedSuite,
                            new SourceRepositoryCreationException(
                                new GitRepositoryException(
                                    new GitActionException(
                                        GitActionException::ACTION_CHECKOUT,
                                        'error: pathspec \'7712df\' did not match any file(s) known to git'
                                    ),
                                ),
                            )
                        ),
                    ];
                },
                'expectedFailureReason' => 'git/checkout',
                'expectedFailureMessage' => 'error: pathspec \'7712df\' did not match any file(s) known to git',
            ],
            'local git repository out of scope' => [
                'handlerFailedExceptionNestedExceptionsCreator' => function (SerializedSuite $serializedSuite) {
                    return [
                        new SuiteSerializationException(
                            $serializedSuite,
                            new SerializeException(
                                new ProvisionException(
                                    new PathTraversalDetected(),
                                ),
                            )
                        ),
                    ];
                },
                'expectedFailureReason' => 'local-git-repository/out-of-scope',
                'expectedFailureMessage' => '/out-of-scope/../../../',
            ],
            'unserializable source type' => [
                'handlerFailedExceptionNestedExceptionsCreator' => function (SerializedSuite $serializedSuite) {
                    return [
                        new SuiteSerializationException(
                            $serializedSuite,
                            new NoSourceRepositoryCreatorException($serializedSuite->suite->getSource())
                        ),
                    ];
                },
                'expectedFailureReason' => 'source/unserializable-type',
                'expectedFailureMessage' => 'git',
            ],
            'unable to write to target' => [
                'handlerFailedExceptionNestedExceptionsCreator' => function (SerializedSuite $serializedSuite) {
                    return [
                        new SuiteSerializationException(
                            $serializedSuite,
                            new UnableToWriteSerializedSuiteException(
                                '/path/that/cannot/be/written/to',
                                'content',
                                new UnableToWriteFile()
                            )
                        ),
                    ];
                },
                'expectedFailureReason' => 'target/write',
                'expectedFailureMessage' => '/path/that/cannot/be/written/to',
            ],
            'unable to read from target' => [
                'handlerFailedExceptionNestedExceptionsCreator' => function (SerializedSuite $serializedSuite) {
                    $unreadableSourceRepository = \Mockery::mock(SourceRepositoryInterface::class);
                    $unreadableSourceRepository
                        ->shouldReceive('getRepositoryPath')
                        ->andReturn('/path/that/cannot/be/read/from')
                    ;

                    return [
                        new SuiteSerializationException(
                            $serializedSuite,
                            new SourceRepositoryReaderNotFoundException($unreadableSourceRepository)
                        ),
                    ];
                },
                'expectedFailureReason' => 'source-repository/read',
                'expectedFailureMessage' => '/path/that/cannot/be/read/from',
            ],
            'unknown' => [
                'handlerFailedExceptionNestedExceptionsCreator' => function (SerializedSuite $serializedSuite) {
                    $unreadableSourceRepository = \Mockery::mock(SourceRepositoryInterface::class);
                    $unreadableSourceRepository
                        ->shouldReceive('getRepositoryPath')
                        ->andReturn('/path/that/cannot/be/read/from')
                    ;

                    return [
                        new SuiteSerializationException(
                            $serializedSuite,
                            new \RuntimeException('An unexpected error occurred')
                        ),
                    ];
                },
                'expectedFailureReason' => 'unknown',
                'expectedFailureMessage' => 'An unexpected error occurred',
            ],
        ];
    }

    private function createSerializedSuite(): SerializedSuite
    {
        $source = new GitSource(
            md5((string) rand()),
            md5((string) rand())
        );
        $source = $source->setLabel(md5((string) rand()));
        if ($source instanceof GitSource) {
            $source->setHostUrl('https://example.com/repository.git');
            $source->setPath('/out-of-scope/../../../');
        }

        $sourceRepository = self::getContainer()->get(SourceRepository::class);
        \assert($sourceRepository instanceof SourceRepository);
        $sourceRepository->save($source);

        $suite = new Suite(md5((string) rand()));
        $suite->setSource($source);
        $suite->setLabel(md5((string) rand()));
        $suiteRepository = self::getContainer()->get(SuiteRepository::class);
        \assert($suiteRepository instanceof SuiteRepository);
        $suiteRepository->save($suite);

        $serializedSuite = new SerializedSuite(md5((string) rand()), $suite);
        $serializedSuiteRepository = self::getContainer()->get(SerializedSuiteRepository::class);
        \assert($serializedSuiteRepository instanceof SerializedSuiteRepository);
        $serializedSuiteRepository->save($serializedSuite);

        return $serializedSuite;
    }

    private function handleEvent(
        SerializedSuite $serializedSuite,
        callable $handlerFailedExceptionNestedExceptionsCreator
    ): int {
        $handlerFailedException = new HandlerFailedException(
            new Envelope(
                new SerializeSuite($serializedSuite->id, [])
            ),
            $handlerFailedExceptionNestedExceptionsCreator($serializedSuite)
        );

        $event = new WorkerMessageFailedEvent(
            new Envelope(new SerializeSuite($serializedSuite->id, [])),
            'async',
            $handlerFailedException
        );

        return $this->handler->handle($event);
    }
}
