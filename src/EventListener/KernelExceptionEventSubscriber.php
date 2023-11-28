<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Exception\DuplicateEntityLabelException;
use App\Exception\DuplicateFilePathException;
use App\Exception\HasHttpErrorCodeInterface;
use App\Exception\InvalidRequestException;
use App\Exception\ModifyReadOnlyEntityException;
use App\FooResponse\ErrorInterface;
use App\FooResponse\RenderableErrorInterface;
use App\FooResponse\SizeInterface;
use App\ResponseBody\ErrorResponse;
use App\ResponseBody\FilesystemExceptionResponse;
use App\ResponseBody\InvalidField;
use App\ResponseBody\InvalidRequestResponse;
use App\Services\ResponseFactory;
use League\Flysystem\FilesystemException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
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

        if ($throwable instanceof ErrorInterface) {
            $response = $this->handleFooHttpError($throwable);
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

        if ($throwable instanceof DuplicateEntityLabelException) {
            $response = $this->handleNonUniqueEntityLabelException($throwable);
        }

        if ($throwable instanceof DuplicateFilePathException) {
            $response = $this->handleDuplicateFilePathException($throwable);
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

    private function handleNonUniqueEntityLabelException(DuplicateEntityLabelException $throwable): Response
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

    private function handleDuplicateFilePathException(DuplicateFilePathException $throwable): Response
    {
        return $this->responseFactory->createErrorResponse(
            new ErrorResponse(
                'duplicate_file_path',
                [
                    'path' => $throwable->path,
                ]
            ),
            400
        );
    }

    private function handleFooHttpError(ErrorInterface $error): Response
    {
        $field = $error->getField();

        $data = [
            'class' => $error->getClass(),
            'field' => [
                'name' => $field->getName(),
                'value' => $field->getValue(),
            ],
        ];

        $type = $error->getType();
        if (is_string($type)) {
            $data['type'] = $type;
        }

        $renderRequirements =
            ($error instanceof RenderableErrorInterface && $error->renderRequirements())
            || !$error instanceof RenderableErrorInterface;

        if ($renderRequirements) {
            $fieldRequirements = $field->getRequirements();

            $requirementsData = [
                'data_type' => $fieldRequirements->getDataType(),
            ];

            $fieldRequirementsSize = $fieldRequirements->getSize();
            if ($fieldRequirementsSize instanceof SizeInterface) {
                $requirementsData['size'] = [
                    'minimum' => $fieldRequirementsSize->getMinimum(),
                    'maximum' => $fieldRequirementsSize->getMaximum(),
                ];
            }

            $data['requirements'] = $requirementsData;
        }

        $statusCode = $error instanceof HasHttpErrorCodeInterface ? $error->getErrorCode() : 400;

        return new JsonResponse(
            $data,
            $statusCode
        );
    }
}
