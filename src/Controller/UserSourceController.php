<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\OriginSourceInterface as OriginSource;
use App\Entity\RunSource;
use App\Entity\SourceInterface;
use App\Exception\File\ReadException;
use App\Exception\InvalidRequestException;
use App\Message\Prepare;
use App\Request\SourceRequestInterface;
use App\ResponseBody\FileExceptionResponse;
use App\Security\UserSourceAccessChecker;
use App\Services\RequestValidator;
use App\Services\ResponseFactory;
use App\Services\RunSourceFactory;
use App\Services\RunSourceSerializer;
use App\Services\Source\Mutator;
use App\Services\Source\Store;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

class UserSourceController
{
    public function __construct(
        private UserSourceAccessChecker $userSourceAccessChecker,
    ) {
    }

    #[Route(SourceRoutes::ROUTE_SOURCE, name: 'user_source_get', methods: ['GET'])]
    public function get(SourceInterface $source): Response
    {
        $this->userSourceAccessChecker->denyAccessUnlessGranted($source);

        return new JsonResponse($source);
    }

    /**
     * @throws InvalidRequestException
     */
    #[Route(SourceRoutes::ROUTE_SOURCE, name: 'user_source_update', methods: ['PUT'])]
    public function update(
        RequestValidator $requestValidator,
        Mutator $mutator,
        OriginSource $source,
        SourceRequestInterface $request,
    ): Response {
        $this->userSourceAccessChecker->denyAccessUnlessGranted($source);
        $requestValidator->validate($request);

        return new JsonResponse($mutator->update($source, $request));
    }

    #[Route(SourceRoutes::ROUTE_SOURCE, name: 'user_source_delete', methods: ['DELETE'])]
    public function delete(SourceInterface $source, Store $store): Response
    {
        $this->userSourceAccessChecker->denyAccessUnlessGranted($source);

        $store->remove($source);

        return new JsonResponse();
    }

    #[Route(SourceRoutes::ROUTE_SOURCE . '/prepare', name: 'user_source_prepare', methods: ['POST'])]
    public function prepare(
        Request $request,
        OriginSource $source,
        MessageBusInterface $messageBus,
        RunSourceFactory $runSourceFactory,
    ): Response {
        $this->userSourceAccessChecker->denyAccessUnlessGranted($source);

        $runSource = $runSourceFactory->createFromRequest($source, $request);
        $messageBus->dispatch(Prepare::createFromRunSource($runSource));

        return new JsonResponse($runSource, 202);
    }

    #[Route(SourceRoutes::ROUTE_SOURCE . '/read', name: 'user_source_read', methods: ['GET'])]
    public function read(
        RunSource $source,
        RunSourceSerializer $runSourceSerializer,
        ResponseFactory $responseFactory,
    ): Response {
        $this->userSourceAccessChecker->denyAccessUnlessGranted($source);

        try {
            return new Response(
                $runSourceSerializer->read($source),
                200,
                [
                    'content-type' => 'text/x-yaml; charset=utf-8',
                ]
            );
        } catch (ReadException $exception) {
            return $responseFactory->createErrorResponse(new FileExceptionResponse($exception), 500);
        }
    }
}
