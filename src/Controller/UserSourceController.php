<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Entity\SourceInterface;
use App\Entity\SourceOriginInterface;
use App\Exception\EmptyEntityIdException;
use App\Exception\InvalidRequestException;
use App\Exception\NonUniqueEntityLabelException;
use App\Message\Prepare;
use App\Repository\SourceRepository;
use App\Request\FileSourceRequest;
use App\Request\GitSourceRequest;
use App\Response\YamlResponse;
use App\Security\EntityAccessChecker;
use App\Services\ExceptionFactory;
use App\Services\RunSourceSerializer;
use App\Services\Source\Mutator;
use App\Services\Source\RunSourceFactory;
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
        private readonly EntityAccessChecker $entityAccessChecker,
    ) {
    }

    /**
     * @throws AccessDeniedException
     */
    #[Route(SourceRoutes::ROUTE_SOURCE, name: 'user_source_get', methods: ['GET'])]
    public function get(SourceInterface $source): Response
    {
        $this->entityAccessChecker->denyAccessUnlessGranted($source);

        return new JsonResponse($source);
    }

    /**
     * @throws AccessDeniedException
     * @throws InvalidRequestException
     */
    #[Route(SourceRoutes::ROUTE_SOURCE . '/file', name: 'user_file_source_update', methods: ['PUT'])]
    public function updateFile(
        Mutator $mutator,
        FileSource $source,
        FileSourceRequest $request,
        ExceptionFactory $exceptionFactory,
    ): Response {
        $this->entityAccessChecker->denyAccessUnlessGranted($source);

        try {
            return new JsonResponse($mutator->updateFile($source, $request));
        } catch (NonUniqueEntityLabelException) {
            throw $exceptionFactory->createInvalidRequestExceptionForNonUniqueEntityLabel(
                $request,
                $request->label,
                'file source'
            );
        }
    }

    /**
     * @throws AccessDeniedException
     * @throws InvalidRequestException
     */
    #[Route(SourceRoutes::ROUTE_SOURCE . '/git', name: 'user_git_source_update', methods: ['PUT'])]
    public function updateGit(
        Mutator $mutator,
        GitSource $source,
        GitSourceRequest $request,
        ExceptionFactory $exceptionFactory,
    ): Response {
        $this->entityAccessChecker->denyAccessUnlessGranted($source);

        try {
            return new JsonResponse($mutator->updateGit($source, $request));
        } catch (NonUniqueEntityLabelException) {
            throw $exceptionFactory->createInvalidRequestExceptionForNonUniqueEntityLabel(
                $request,
                $request->label,
                'git source'
            );
        }
    }

    /**
     * @throws AccessDeniedException
     * @throws FilesystemException
     */
    #[Route(SourceRoutes::ROUTE_SOURCE, name: 'user_source_delete', methods: ['DELETE'])]
    public function delete(
        SourceInterface $source,
        SourceRepository $sourceRepository,
        FilesystemWriter $fileSourceWriter,
        FilesystemWriter $runSourceWriter,
    ): Response {
        $this->entityAccessChecker->denyAccessUnlessGranted($source);

        $sourceRepository->delete($source);

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
     * @throws EmptyEntityIdException
     */
    #[Route(SourceRoutes::ROUTE_SOURCE . '/prepare', name: 'user_source_prepare', methods: ['POST'])]
    public function prepare(
        Request $request,
        SourceOriginInterface $source,
        MessageBusInterface $messageBus,
        RunSourceFactory $runSourceFactory,
    ): Response {
        $this->entityAccessChecker->denyAccessUnlessGranted($source);

        $runSource = $runSourceFactory->create($source, $request);
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
        $this->entityAccessChecker->denyAccessUnlessGranted($source);

        return new YamlResponse($runSourceSerializer->read($source));
    }
}
