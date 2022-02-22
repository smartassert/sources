<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\FileSource;
use App\Entity\OriginSourceInterface as OriginSource;
use App\Entity\RunSource;
use App\Entity\SourceInterface;
use App\Exception\InvalidRequestException;
use App\Message\Prepare;
use App\Request\SourceRequestInterface;
use App\Security\UserSourceAccessChecker;
use App\Services\RequestValidator;
use App\Services\RunSourceFactory;
use App\Services\RunSourceSerializer;
use App\Services\Source\Mutator;
use App\Services\Source\Store;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemWriter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class UserSourceController
{
    public function __construct(
        private UserSourceAccessChecker $userSourceAccessChecker,
    ) {
    }

    /**
     * @throws AccessDeniedException
     */
    #[Route(SourceRoutes::ROUTE_SOURCE, name: 'user_source_get', methods: ['GET'])]
    public function get(SourceInterface $source): Response
    {
        $this->userSourceAccessChecker->denyAccessUnlessGranted($source);

        return new JsonResponse($source);
    }

    /**
     * @throws AccessDeniedException
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

    /**
     * @throws AccessDeniedException
     * @throws FilesystemException
     */
    #[Route(SourceRoutes::ROUTE_SOURCE, name: 'user_source_delete', methods: ['DELETE'])]
    public function delete(
        SourceInterface $source,
        Store $store,
        FilesystemWriter $fileSourceWriter,
        FilesystemWriter $runSourceWriter,
    ): Response {
        $this->userSourceAccessChecker->denyAccessUnlessGranted($source);

        $store->remove($source);

        if ($source instanceof FileSource) {
            $fileSourceWriter->deleteDirectory($source->getDirectoryPath());
        }

        if ($source instanceof RunSource) {
            $runSourceWriter->deleteDirectory($source->getDirectoryPath());
        }

        return new JsonResponse();
    }

    /**
     * @throws AccessDeniedException
     */
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

    /**
     * @throws AccessDeniedException
     * @throws FilesystemException
     */
    #[Route(SourceRoutes::ROUTE_SOURCE . '/read', name: 'user_source_read', methods: ['GET'])]
    public function read(RunSource $source, RunSourceSerializer $runSourceSerializer): Response
    {
        $this->userSourceAccessChecker->denyAccessUnlessGranted($source);

        return new Response($runSourceSerializer->read($source), 200, [
            'content-type' => 'text/x-yaml; charset=utf-8',
        ]);
    }
}
