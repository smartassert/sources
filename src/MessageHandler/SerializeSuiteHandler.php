<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\SerializedSuite;
use App\Enum\SerializedSuite\State;
use App\Exception\MessageHandler\SuiteSerializationException;
use App\Message\SerializeSuite;
use App\Repository\SerializedSuiteRepository;
use App\Services\SuiteSerializer;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class SerializeSuiteHandler
{
    public function __construct(
        private readonly SerializedSuiteRepository $serializedSuiteRepository,
        private readonly SuiteSerializer $suiteSerializer,
    ) {
    }

    /**
     * @throws SuiteSerializationException
     */
    public function __invoke(SerializeSuite $message): void
    {
        $serializedSuite = $this->serializedSuiteRepository->find($message->getSuiteId());
        if (
            !$serializedSuite instanceof SerializedSuite
            || !in_array($serializedSuite->getState(), [State::REQUESTED, State::PREPARING_HALTED])
        ) {
            return;
        }

        $serializedSuite->setState(State::PREPARING_RUNNING);
        $this->serializedSuiteRepository->save($serializedSuite);

        try {
            $this->suiteSerializer->write($serializedSuite);
        } catch (\Throwable $e) {
            $serializedSuite->setState(State::PREPARING_HALTED);
            $this->serializedSuiteRepository->save($serializedSuite);

            throw new SuiteSerializationException($serializedSuite, $e);
        }

        $serializedSuite->setState(State::PREPARED);
        $this->serializedSuiteRepository->save($serializedSuite);
    }
}
