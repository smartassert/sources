<?php

declare(strict_types=1);

namespace App\MessageDispatcher;

use App\Event\SerializedSuiteCreatedEvent;
use App\Message\SerializeSuite;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Exception\ExceptionInterface as MessengerExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class SerializeSuiteMessageDispatcher implements EventSubscriberInterface
{
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {}

    /**
     * @return array<class-string, array<mixed>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            SerializedSuiteCreatedEvent::class => [
                ['dispatch', 500],
            ],
        ];
    }

    /**
     * @throws MessengerExceptionInterface
     */
    public function dispatch(SerializedSuiteCreatedEvent $event): void
    {
        $this->messageBus->dispatch(SerializeSuite::createFromSerializedSuite($event->serializedSuite));
    }
}
