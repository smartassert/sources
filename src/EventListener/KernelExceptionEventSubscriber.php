<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Exception\HasHttpErrorCodeInterface;
use App\Exception\InvalidRequestException;
use App\ResponseBody\FilesystemExceptionResponse;
use App\Services\InvalidRequestResponseFactory;
use App\Services\ResponseFactory;
use League\Flysystem\FilesystemException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

class KernelExceptionEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ResponseFactory $responseFactory,
        private InvalidRequestResponseFactory $invalidRequestResponseFactory,
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

        if ($throwable instanceof HasHttpErrorCodeInterface) {
            $response = $this->handleHttpErrorException($throwable);
        }

        if ($throwable instanceof InvalidRequestException) {
            $response = $this->handleInvalidRequest($throwable);
        }

        if ($throwable instanceof FilesystemException) {
            $response = $this->handleFilesystemException($throwable);
        }

        if ($response instanceof Response) {
            $event->setResponse($response);
            $event->stopPropagation();
        }
    }

    private function handleHttpErrorException(HasHttpErrorCodeInterface $throwable): Response
    {
        return new Response(null, $throwable->getErrorCode());
    }

    private function handleInvalidRequest(InvalidRequestException $throwable): Response
    {
        return $this->responseFactory->createErrorResponse(
            $this->invalidRequestResponseFactory->createFromConstraintViolation(
                $throwable->getViolation(),
                $throwable->getPropertyNamePrefixesToRemove()
            ),
            $throwable->getErrorCode()
        );
    }

    private function handleFilesystemException(FilesystemException $throwable): Response
    {
        return $this->responseFactory->createErrorResponse(new FilesystemExceptionResponse($throwable), 500);
    }
}
