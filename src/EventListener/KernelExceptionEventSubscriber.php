<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Exception\EntityStorageException;
use App\Exception\HasHttpErrorCodeInterface;
use App\Exception\ModifyReadOnlyEntityException;
use App\FooRequest\CollectionFieldInterface;
use App\FooRequest\RequirementsInterface;
use App\FooRequest\ScalarRequirementsInterface;
use App\FooResponse\ErrorInterface;
use App\FooResponse\RenderableErrorInterface;
use App\FooResponse\SizeInterface;
use App\ResponseBody\ErrorResponse;
use App\ResponseBody\FilesystemExceptionResponse;
use App\Services\ResponseFactory;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperationFailed;
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

        if ($throwable instanceof EntityStorageException) {
            $response = $this->handleEntityStorageException($throwable);
        }

        if ($throwable instanceof FilesystemException) {
            $response = $this->handleFilesystemException($throwable);
        }

        if ($throwable instanceof ModifyReadOnlyEntityException) {
            $response = $this->handleModifyReadOnlyEntityException($throwable);
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

    private function handleFilesystemException(FilesystemException $throwable): Response
    {
        return $this->responseFactory->createErrorResponse(new FilesystemExceptionResponse($throwable), 500);
    }

    private function handleEntityStorageException(EntityStorageException $throwable): Response
    {
        $filesystemException = $throwable->filesystemException;
        $operationType = 'unknown';
        if ($filesystemException instanceof FilesystemOperationFailed) {
            $operationType = strtolower($filesystemException->operation());
        }

        $location = null;
        if (method_exists($filesystemException, 'location')) {
            $location = $filesystemException->location();
        }

        return new JsonResponse(
            [
                'class' => 'entity_storage',
                'entity' => [
                    'type' => $throwable->entity->getEntityType()->value,
                    'id' => $throwable->entity->getId(),
                ],
                'type' => $operationType,
                'location' => $location,
            ],
            500
        );
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

    private function handleFooHttpError(ErrorInterface $error): Response
    {
        $field = $error->getField();

        $data = [
            'class' => $error->getClass(),
        ];

        $fieldData = [
            'name' => $field->getName(),
            'value' => $field->getValue(),
        ];

        if ($field instanceof CollectionFieldInterface) {
            $fieldData['position'] = $field->getErrorPosition();
        }

        $data['field'] = $fieldData;

        $type = $error->getType();
        if (is_string($type)) {
            $data['type'] = $type;
        }

        $fieldRequirements = $field->getRequirements();

        $renderRequirements =
            ($error instanceof RenderableErrorInterface && $error->renderRequirements())
            || !$error instanceof RenderableErrorInterface;

        if ($renderRequirements && $fieldRequirements instanceof RequirementsInterface) {
            $requirementsData = [
                'data_type' => $fieldRequirements->getDataType(),
            ];

            if ($fieldRequirements instanceof ScalarRequirementsInterface) {
                $fieldRequirementsSize = $fieldRequirements->getSize();
                if ($fieldRequirementsSize instanceof SizeInterface) {
                    $requirementsData['size'] = [
                        'minimum' => $fieldRequirementsSize->getMinimum(),
                        'maximum' => $fieldRequirementsSize->getMaximum(),
                    ];
                }
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
