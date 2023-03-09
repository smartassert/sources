<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\SerializedSuite;
use App\Entity\Suite;
use App\Exception\EmptyEntityIdException;
use App\Message\SerializeSuite;
use App\Response\YamlResponse;
use App\Security\EntityAccessChecker;
use App\Services\Suite\SerializedSuiteFactory;
use App\Services\SuiteSerializer;
use League\Flysystem\FilesystemException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class SerializedSuiteController
{
    public function __construct(
        private readonly EntityAccessChecker $entityAccessChecker,
    ) {
    }

    /**
     * @throws AccessDeniedException
     * @throws EmptyEntityIdException
     */
    #[Route(SuiteRoutes::ROUTE_SUITE . '/serialize', name: 'user_suite_serialize', methods: ['POST'])]
    public function serialize(
        Request $request,
        Suite $suite,
        MessageBusInterface $messageBus,
        SerializedSuiteFactory $serializedSuiteFactory,
    ): Response {
        $this->entityAccessChecker->denyAccessUnlessGranted($suite);

        $serializedSuite = $serializedSuiteFactory->create($suite, $request);

        $messageBus->dispatch(SerializeSuite::createFromSerializedSuite($serializedSuite));

        return new JsonResponse($serializedSuite, 202);
    }

    /**
     * @throws AccessDeniedException
     * @throws FilesystemException
     */
    #[Route(SuiteRoutes::ROUTE_SUITE . '/read', name: 'user_suite_read', methods: ['GET'])]
    public function read(SerializedSuite $serializedSuite, SuiteSerializer $suiteSerializer): Response
    {
        $this->entityAccessChecker->denyAccessUnlessGranted($serializedSuite);

        return new YamlResponse($suiteSerializer->read($serializedSuite));
    }
}
