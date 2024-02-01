<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\SerializedSuite;
use App\Exception\ErrorResponseException;
use App\Exception\ErrorResponseExceptionFactory;
use App\Exception\SerializedSuiteSourceDoesNotExistException;
use App\Exception\StorageExceptionFactory;
use App\Message\SerializeSuite;
use App\Repository\SerializedSuiteRepository;
use App\Request\CreateSerializedSuiteRequest;
use App\Response\YamlResponse;
use App\Services\SuiteSerializer;
use League\Flysystem\FilesystemException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

class SerializedSuiteController
{
    #[Route(
        path: SuiteRoutes::ROUTE_SUITE . '/' . SerializedSuiteRoutes::ROUTE_SUITE_ID_PATTERN,
        name: 'serialized_suite_create',
        methods: ['POST']
    )]
    public function create(
        CreateSerializedSuiteRequest $request,
        SerializedSuiteRepository $repository,
        MessageBusInterface $messageBus,
    ): Response {
        $serializedSuite = $repository->find($request->id);
        if (null === $serializedSuite) {
            $serializedSuite = new SerializedSuite($request->id, $request->suite, $request->runParameters);
            $repository->save($serializedSuite);
            $messageBus->dispatch(SerializeSuite::createFromSerializedSuite($serializedSuite));
        }

        return new JsonResponse($serializedSuite, 202);
    }

    /**
     * @throws ErrorResponseException
     */
    #[Route(SerializedSuiteRoutes::ROUTE_SERIALIZED_SUITE . '/read', name: 'serialized_suite_read', methods: ['GET'])]
    public function read(
        SerializedSuite $serializedSuite,
        SuiteSerializer $suiteSerializer,
        ErrorResponseExceptionFactory $exceptionFactory,
        StorageExceptionFactory $storageExceptionFactory,
    ): Response {
        try {
            return new YamlResponse($suiteSerializer->read($serializedSuite));
        } catch (SerializedSuiteSourceDoesNotExistException) {
            return new Response(null, 404);
        } catch (FilesystemException $e) {
            throw $exceptionFactory->createForStorageError(
                $storageExceptionFactory->createForEntityStorageFailure($serializedSuite, $e)
            );
        }
    }

    #[Route(SerializedSuiteRoutes::ROUTE_SERIALIZED_SUITE, name: 'serialized_suite_get', methods: ['GET'])]
    public function get(SerializedSuite $serializedSuite): Response
    {
        return new JsonResponse($serializedSuite);
    }
}
