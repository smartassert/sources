<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\SerializedSuite;
use App\Exception\EmptyEntityIdException;
use App\Exception\SerializedSuiteSourceDoesNotExistException;
use App\Message\SerializeSuite;
use App\Repository\SerializedSuiteRepository;
use App\Request\CreateSerializedSuiteRequest;
use App\Response\YamlResponse;
use App\Services\EntityIdFactory;
use App\Services\SuiteSerializer;
use League\Flysystem\FilesystemException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

class SerializedSuiteController
{
    /**
     * @throws EmptyEntityIdException
     */
    #[Route(SuiteRoutes::ROUTE_SUITE . '/serialize', name: 'serialized_suite_create', methods: ['POST'])]
    public function create(
        CreateSerializedSuiteRequest $request,
        EntityIdFactory $entityIdFactory,
        SerializedSuiteRepository $repository,
        MessageBusInterface $messageBus,
    ): Response {
        $serializedSuite = new SerializedSuite($entityIdFactory->create(), $request->suite, $request->runParameters);
        $repository->save($serializedSuite);

        $messageBus->dispatch(SerializeSuite::createFromSerializedSuite($serializedSuite));

        return new JsonResponse($serializedSuite, 202);
    }

    /**
     * @throws FilesystemException
     */
    #[Route(SerializedSuiteRoutes::ROUTE_SERIALIZED_SUITE . '/read', name: 'serialized_suite_read', methods: ['GET'])]
    public function read(
        SerializedSuite $serializedSuite,
        SuiteSerializer $suiteSerializer,
    ): Response {
        try {
            return new YamlResponse($suiteSerializer->read($serializedSuite));
        } catch (SerializedSuiteSourceDoesNotExistException) {
            return new Response(null, 404);
        }
    }

    #[Route(SerializedSuiteRoutes::ROUTE_SERIALIZED_SUITE, name: 'serialized_suite_get', methods: ['GET'])]
    public function get(SerializedSuite $serializedSuite): Response
    {
        return new JsonResponse($serializedSuite);
    }
}
