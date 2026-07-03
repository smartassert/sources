<?php

declare(strict_types=1);

namespace App\Services;

use App\Event\SerializedSuiteCreatedEvent;
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
        ];
    }

    public function persist(SerializedSuiteCreatedEvent $event): void
    {
        $this->serializedSuiteRepository->save($event->serializedSuite);
    }
}
