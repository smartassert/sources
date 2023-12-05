<?php

declare(strict_types=1);

namespace App\EventListener;

use App\ErrorResponse\ErrorInterface;
use App\ErrorResponse\Factory;
use App\ErrorResponse\HasHttpStatusCodeInterface;
use App\ErrorResponse\SerializableBadRequestErrorInterface;
use App\ErrorResponse\SerializableStorageErrorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

readonly class KernelExceptionEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Factory $errorResponseFactory,
    ) {
    }

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

        if ($throwable instanceof SerializableStorageErrorInterface) {
            $event->setResponse(new JsonResponse($throwable, 500));
            $event->stopPropagation();

            return;
        }

        if ($throwable instanceof SerializableBadRequestErrorInterface) {
            $event->setResponse(new JsonResponse($throwable, 400));
            $event->stopPropagation();

            return;
        }

        if ($throwable instanceof ErrorInterface) {
            $event->setResponse($this->errorResponseFactory->create($throwable));
            $event->stopPropagation();

            return;
        }

        if ($throwable instanceof HasHttpStatusCodeInterface) {
            $event->setResponse(new Response(null, $throwable->getStatusCode()));
            $event->stopPropagation();
        }
    }
}
