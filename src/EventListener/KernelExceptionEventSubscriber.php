<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Exception\HasHttpErrorCodeInterface;
use App\FooRequest\CollectionFieldInterface;
use App\FooRequest\RequirementsInterface;
use App\FooRequest\ScalarRequirementsInterface;
use App\FooResponse\ErrorInterface;
use App\FooResponse\RenderableErrorInterface;
use App\FooResponse\SizeInterface;
use App\ResponseBody\FilesystemExceptionResponse;
use League\Flysystem\FilesystemException;
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
        $response = null;

        if ($throwable instanceof HasHttpErrorCodeInterface) {
            $response = $this->handleHttpErrorException($throwable);
        }

        if ($throwable instanceof ErrorInterface) {
            $response = $this->handleFooHttpError($throwable);
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

    private function handleFilesystemException(FilesystemException $throwable): Response
    {
        $error = new FilesystemExceptionResponse($throwable);

        return new JsonResponse(
            [
                'error' => [
                    'type' => $error->getType(),
                    'payload' => $error->getPayload(),
                ],
            ],
            500
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
