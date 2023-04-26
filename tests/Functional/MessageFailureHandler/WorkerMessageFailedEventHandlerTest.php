<?php

declare(strict_types=1);

namespace App\Tests\Functional\MessageFailureHandler;

use App\Entity\FileSource;
use App\Entity\SerializedSuite;
use App\Entity\Suite;
use App\Enum\SerializedSuite\State;
use App\Exception\GitActionException;
use App\Exception\GitRepositoryException;
use App\Exception\MessageHandler\SuiteSerializationException;
use App\Exception\SourceRepositoryCreationException;
use App\Message\SerializeSuite;
use App\MessageFailureHandler\WorkerMessageFailedEventHandler;
use App\Repository\SerializedSuiteRepository;
use App\Repository\SourceRepository;
use App\Repository\SuiteRepository;
use App\Tests\Services\EntityRemover;
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
            'incorrect message type' => [
                'event' => new WorkerMessageFailedEvent(
                    new Envelope(new \stdClass()),
                    'async',
                    \Mockery::mock(HandlerFailedException::class),
                ),
                'expectedReturnState' => WorkerMessageFailedEventHandler::STATE_INCORRECT_MESSAGE_TYPE,
            ],
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

        $returnState = $this->foo($serializedSuite, $handlerFailedExceptionNestedExceptionsCreator);

        self::assertSame(WorkerMessageFailedEventHandler::STATE_SUCCESS, $returnState);
        self::assertSame(State::REQUESTED, $serializedSuite->getState());
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
            'no relevant suite serialization exception' => [
                'handlerFailedExceptionNestedExceptionsCreator' => function (SerializedSuite $serializedSuite) {
                    return [
                        new SuiteSerializationException($serializedSuite, new \Exception()),
                    ];
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
        self::assertSame(State::REQUESTED, $serializedSuite->getState());

        $returnState = $this->foo($serializedSuite, $handlerFailedExceptionNestedExceptionsCreator);
        self::assertSame(WorkerMessageFailedEventHandler::STATE_SUCCESS, $returnState);
        self::assertSame(State::FAILED, $serializedSuite->getState());

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
        ];
    }

    private function createSerializedSuite(): SerializedSuite
    {
        $source = new FileSource(md5((string) rand()), md5((string) rand()));
        $source = $source->setLabel(md5((string) rand()));
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

    private function foo(SerializedSuite $serializedSuite, callable $handlerFailedExceptionNestedExceptionsCreator): int
    {
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
