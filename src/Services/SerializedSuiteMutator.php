<?php

declare(strict_types=1);

namespace App\Services;

use App\Event\SerializedSuiteCreatedEvent;
use App\Event\SerializedSuitePreparationFailedEvent;
use App\Repository\SerializedSuiteRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class SerializedSuiteMutator implements EventSubscriberInterface
{
    public function __construct(
        private SerializedSuiteRepository $serializedSuiteRepository,
    ) {}

    /**
     * @return array<class-string, array<mixed>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            SerializedSuiteCreatedEvent::class => [
                ['persist', 1000],
            ],
            SerializedSuitePreparationFailedEvent::class => [
                ['setPreparationFailed', 100],
            ],
        ];
    }

    public function persist(SerializedSuiteCreatedEvent $event): void
    {
        $this->serializedSuiteRepository->save($event->serializedSuite);
    }

    public function setPreparationFailed(SerializedSuitePreparationFailedEvent $event): void
    {
        $serializedSuite = $event->serializedSuite;
        $serializedSuite = $serializedSuite->setPreparationFailed($event->failureReason, $event->failureMessage);

        $this->serializedSuiteRepository->save($serializedSuite);
    }
}
