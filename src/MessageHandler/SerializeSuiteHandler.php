<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\SerializedSuiteInterface;
use App\Enum\SerializedSuite\State;
use App\Event\SerializedSuiteStateChangedEvent;
use App\Exception\MessageHandler\SerializeSuiteException;
use App\Message\SerializeSuite;
use App\Repository\SerializedSuiteRepository;
use App\Services\SerializeSuiteExceptionFactory\SerializeSuiteExceptionFactory;
use App\Services\SuiteSerializer;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsMessageHandler]
class SerializeSuiteHandler
{
    public function __construct(
        private readonly SerializedSuiteRepository $serializedSuiteRepository,
        private readonly SuiteSerializer $suiteSerializer,
        private readonly SerializeSuiteExceptionFactory $serializeSuiteExceptionFactory,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    /**
     * @throws SerializeSuiteException
     */
    public function __invoke(SerializeSuite $message): void
    {
        $serializedSuite = $this->serializedSuiteRepository->find($message->getSuiteId());
        if (
            !$serializedSuite instanceof SerializedSuiteInterface
            || !in_array($serializedSuite->getState(), [State::REQUESTED, State::PREPARING_HALTED])
        ) {
            return;
        }

        $this->eventDispatcher->dispatch(new SerializedSuiteStateChangedEvent(
            $serializedSuite,
            State::PREPARING_RUNNING
        ));

        try {
            $this->suiteSerializer->write($serializedSuite);
        } catch (\Throwable $e) {
            $this->eventDispatcher->dispatch(new SerializedSuiteStateChangedEvent(
                $serializedSuite,
                State::PREPARING_HALTED
            ));

            throw $this->serializeSuiteExceptionFactory->create($serializedSuite, $e);
        }

        $this->eventDispatcher->dispatch(new SerializedSuiteStateChangedEvent(
            $serializedSuite,
            State::PREPARED
        ));
    }
}
