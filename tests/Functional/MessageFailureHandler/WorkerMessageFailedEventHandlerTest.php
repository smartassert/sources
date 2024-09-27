<?php

declare(strict_types=1);

namespace App\Tests\Functional\MessageFailureHandler;

use App\Entity\GitSource;
use App\Entity\SerializedSuite;
use App\Entity\Suite;
use App\Enum\SerializedSuite\FailureReason;
use App\Enum\SerializedSuite\State;
use App\Exception\MessageHandler\SerializeSuiteException;
use App\Exception\NoSourceRepositoryCreatorException;
use App\Message\SerializeSuite;
use App\Repository\SerializedSuiteRepository;
use App\Repository\SourceRepository;
use App\Repository\SuiteRepository;
use App\Tests\Services\EntityRemover;
use App\Tests\Services\StringFactory;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\DataProvider;
use SmartAssert\WorkerMessageFailedEventBundle\ExceptionHandlerInterface;
use SmartAssert\WorkerMessageFailedEventBundle\WorkerMessageFailedEventHandler;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Exception\HandlerFailedException;

class WorkerMessageFailedEventHandlerTest extends WebTestCase
{
    use MockeryPHPUnitIntegration;

    private WorkerMessageFailedEventHandler $handler;

    #[\Override]
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

    #[DataProvider('invokeDoesNotHandleDataProvider')]
    public function testInvokeDoesNotHandle(WorkerMessageFailedEvent $event): void
    {
        $exceptionCollectionHandler = \Mockery::mock(ExceptionHandlerInterface::class);
        $exceptionCollectionHandler
            ->shouldNotReceive('handle')
        ;

        (new WorkerMessageFailedEventHandler([$exceptionCollectionHandler]))($event);
    }

    /**
     * @return array<mixed>
     */
    public static function invokeDoesNotHandleDataProvider(): array
    {
        return [
            'event will retry' => [
                'event' => (function () {
                    $event = new WorkerMessageFailedEvent(
                        new Envelope(new SerializeSuite(StringFactory::createRandom(), [])),
                        'async',
                        \Mockery::mock(HandlerFailedException::class),
                    );
                    $event->setForRetry();

                    return $event;
                })(),
            ],
        ];
    }

    /**
     * @param callable(SerializedSuite): \Exception[] $handlerFailedExceptionNestedExceptionsCreator
     */
    #[DataProvider('handleNoSerializedSuiteStateChangeDataProvider')]
    public function testHandleNoSerializedSuiteStateChange(
        callable $handlerFailedExceptionNestedExceptionsCreator
    ): void {
        $serializedSuite = $this->createSerializedSuite();
        self::assertSame(State::REQUESTED, $serializedSuite->getState());

        $this->handleEvent($serializedSuite, $handlerFailedExceptionNestedExceptionsCreator);

        self::assertSame(State::REQUESTED->value, $serializedSuite->getState()->value);
    }

    /**
     * @return array<mixed>
     */
    public static function handleNoSerializedSuiteStateChangeDataProvider(): array
    {
        return [
            'no relevant nested exceptions' => [
                'handlerFailedExceptionNestedExceptionsCreator' => function () {
                    return [new \Exception()];
                },
            ],
        ];
    }

    public function testHandleHasSerializedSuiteStateChange(): void
    {
        $type = StringFactory::createRandom();

        $handlerFailedExceptionNestedExceptionsCreator = function (SerializedSuite $serializedSuite) use ($type) {
            return [
                new SerializeSuiteException(
                    $serializedSuite,
                    new NoSourceRepositoryCreatorException($serializedSuite->suite->getSource()),
                    FailureReason::UNSERIALIZABLE_SOURCE_TYPE,
                    $type
                ),
            ];
        };

        $expectedFailureReason = 'source/unserializable-type';
        $expectedFailureMessage = $type;

        $serializedSuite = $this->createSerializedSuite();
        self::assertSame(State::REQUESTED->value, $serializedSuite->getState()->value);

        $this->handleEvent($serializedSuite, $handlerFailedExceptionNestedExceptionsCreator);
        self::assertSame(State::FAILED->value, $serializedSuite->getState()->value);

        $serializedSuiteData = $serializedSuite->jsonSerialize();
        self::assertArrayHasKey('failure_reason', $serializedSuiteData);
        self::assertArrayHasKey('failure_message', $serializedSuiteData);
        self::assertSame($expectedFailureReason, $serializedSuiteData['failure_reason']);
        self::assertSame($expectedFailureMessage, $serializedSuiteData['failure_message']);
    }

    private function createSerializedSuite(): SerializedSuite
    {
        $source = new GitSource(
            StringFactory::createRandom(),
            StringFactory::createRandom()
        );
        $source = $source->setLabel(StringFactory::createRandom());
        if ($source instanceof GitSource) {
            $source->setHostUrl('https://example.com/repository.git');
            $source->setPath('/out-of-scope/../../../');
        }

        $sourceRepository = self::getContainer()->get(SourceRepository::class);
        \assert($sourceRepository instanceof SourceRepository);
        $sourceRepository->save($source);

        $suite = new Suite(StringFactory::createRandom());
        $suite->setSource($source);
        $suite->setLabel(StringFactory::createRandom());
        $suiteRepository = self::getContainer()->get(SuiteRepository::class);
        \assert($suiteRepository instanceof SuiteRepository);
        $suiteRepository->save($suite);

        $serializedSuite = new SerializedSuite(StringFactory::createRandom(), $suite);
        $serializedSuiteRepository = self::getContainer()->get(SerializedSuiteRepository::class);
        \assert($serializedSuiteRepository instanceof SerializedSuiteRepository);
        $serializedSuiteRepository->save($serializedSuite);

        return $serializedSuite;
    }

    private function handleEvent(
        SerializedSuite $serializedSuite,
        callable $handlerFailedExceptionNestedExceptionsCreator
    ): void {
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

        ($this->handler)($event);
    }
}
