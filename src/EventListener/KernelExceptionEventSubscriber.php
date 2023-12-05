<?php

declare(strict_types=1);

namespace App\EventListener;

use App\ErrorResponse\HasHttpStatusCodeInterface;
use App\ErrorResponse\SerializableErrorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

readonly class KernelExceptionEventSubscriber implements EventSubscriberInterface
{
    /**
     * @return array<class-string, array<mixed>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ExceptionEvent::class => [
                ['onKernelException', 100],
            ],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();

        if ($throwable instanceof SerializableErrorInterface) {
            $event->setResponse(new JsonResponse($throwable, $throwable->getStatusCode()));
            $event->stopPropagation();

            return;
        }

        if ($throwable instanceof HasHttpStatusCodeInterface) {
            $event->setResponse(new Response(null, $throwable->getStatusCode()));
            $event->stopPropagation();
        }
    }
}
