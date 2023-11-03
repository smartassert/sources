<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Exception\HasHttpErrorCodeInterface;
use App\Exception\InvalidRequestException;
use App\Exception\ModifyReadOnlyEntityException;
use App\Exception\NonUniqueEntityLabelException;
use App\ResponseBody\ErrorResponse;
use App\ResponseBody\FilesystemExceptionResponse;
use App\ResponseBody\InvalidField;
use App\ResponseBody\InvalidRequestResponse;
use App\Services\ResponseFactory;
use League\Flysystem\FilesystemException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

readonly class KernelExceptionEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ResponseFactory $responseFactory,
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

        if ($throwable instanceof ModifyReadOnlyEntityException) {
            $response = $this->handleModifyReadOnlyEntityException($throwable);
        }

        if ($throwable instanceof NonUniqueEntityLabelException) {
            $response = $this->handleNonUniqueEntityLabelException($throwable);
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
            new InvalidRequestResponse($throwable->getInvalidField()),
            $throwable->getErrorCode()
        );
    }

    private function handleFilesystemException(FilesystemException $throwable): Response
    {
        return $this->responseFactory->createErrorResponse(new FilesystemExceptionResponse($throwable), 500);
    }

    private function handleModifyReadOnlyEntityException(ModifyReadOnlyEntityException $throwable): Response
    {
        return $this->responseFactory->createErrorResponse(
            new ErrorResponse(
                'modify-read-only-entity',
                [
                    'type' => $throwable->type,
                    'id' => $throwable->id,
                ]
            ),
            $throwable->getErrorCode()
        );
    }

    private function handleNonUniqueEntityLabelException(NonUniqueEntityLabelException $throwable): Response
    {
        $request = $throwable->request;

        $invalidField = new InvalidField(
            'label',
            $request->getLabel(),
            sprintf('This label is being used by another %s belonging to this user', $request->getObjectType())
        );

        return $this->responseFactory->createErrorResponse(
            new InvalidRequestResponse($invalidField),
            400
        );
    }
}
