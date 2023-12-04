<?php

declare(strict_types=1);

namespace App\EventListener;

use App\ErrorResponse\ErrorInterface;
use App\ErrorResponse\Factory;
use App\ErrorResponse\HasHttpStatusCodeInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
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
        $response = null;

        if ($throwable instanceof HasHttpStatusCodeInterface) {
            $response = new Response(null, $throwable->getStatusCode());
        }

        if ($throwable instanceof ErrorInterface) {
            $response = $this->errorResponseFactory->create($throwable);
        }

        if ($response instanceof Response) {
            $event->setResponse($response);
            $event->stopPropagation();
        }
    }
}
