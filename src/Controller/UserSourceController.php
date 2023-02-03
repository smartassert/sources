<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\FileSource;
use App\Entity\RunSource;
use App\Entity\SourceInterface;
use App\Entity\SourceOriginInterface;
use App\Exception\InvalidRequestException;
use App\Message\Prepare;
use App\Request\FileSourceRequest;
use App\Request\GitSourceRequest;
use App\Response\YamlResponse;
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
    #[Route(SourceRoutes::ROUTE_SOURCE . '/file', name: 'user_file_source_update', methods: ['PUT'])]
    public function updateFile(
        RequestValidator $requestValidator,
        Mutator $mutator,
        SourceOriginInterface $source,
        FileSourceRequest $request,
    ): Response {
        return $this->foo($requestValidator, $mutator, $source, $request);
    }

    /**
     * @throws AccessDeniedException
     * @throws InvalidRequestException
     */
    #[Route(SourceRoutes::ROUTE_SOURCE . '/git', name: 'user_git_source_update', methods: ['PUT'])]
    public function updateGit(
        RequestValidator $requestValidator,
        Mutator $mutator,
        SourceOriginInterface $source,
        GitSourceRequest $request,
    ): Response {
        return $this->foo($requestValidator, $mutator, $source, $request);
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

        return new Response();
    }

    /**
     * @throws AccessDeniedException
     */
    #[Route(SourceRoutes::ROUTE_SOURCE . '/prepare', name: 'user_source_prepare', methods: ['POST'])]
    public function prepare(
        Request $request,
        SourceOriginInterface $source,
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

        return new YamlResponse($runSourceSerializer->read($source));
    }

    private function foo(
        RequestValidator $requestValidator,
        Mutator $mutator,
        SourceOriginInterface $source,
        FileSourceRequest|GitSourceRequest $request,
    ): Response {
        $this->userSourceAccessChecker->denyAccessUnlessGranted($source);
        $requestValidator->validate($request);

        return new JsonResponse($mutator->update($source, $request));
    }
}
