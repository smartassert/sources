<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Entity\SourceInterface;
use App\Entity\SourceOriginInterface;
use App\Enum\Source\Type;
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
use App\Services\Source\FileSourceFactory;
use App\Services\Source\GitSourceFactory;
use App\Services\Source\Mutator;
use App\Services\Source\RunSourceFactory;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemWriter;
use SmartAssert\UsersSecurityBundle\Security\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserInterface;

class SourceController
{
    public function __construct(
        private readonly SourceRepository $repository,
        private readonly ExceptionFactory $exceptionFactory,
        private readonly EntityAccessChecker $entityAccessChecker,
    ) {
    }

    /**
     * @throws EmptyEntityIdException
     * @throws InvalidRequestException
     */
    #[Route('/source', name: 'source_create', methods: ['POST'])]
    public function create(
        User $user,
        FileSourceRequest|GitSourceRequest $request,
        FileSourceFactory $fileSourceFactory,
        GitSourceFactory $gitSourceFactory,
    ): JsonResponse {
        try {
            if ($request instanceof FileSourceRequest) {
                return new JsonResponse($fileSourceFactory->create($user, $request));
            }

            return new JsonResponse($gitSourceFactory->create($user, $request));
        } catch (NonUniqueEntityLabelException) {
            throw $this->exceptionFactory->createInvalidRequestExceptionForNonUniqueEntityLabel(
                $request,
                $request->label,
                'source'
            );
        }
    }

    #[Route('/sources', name: 'source_list', methods: ['GET'])]
    public function list(UserInterface $user): JsonResponse
    {
        return new JsonResponse($this->repository->findNonDeletedByUserAndType($user, [Type::FILE, Type::GIT]));
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
    #[Route(SourceRoutes::ROUTE_SOURCE, name: 'user_source_update', methods: ['PUT'])]
    public function update(
        Mutator $mutator,
        SourceOriginInterface $source,
        FileSourceRequest|GitSourceRequest $request,
        ExceptionFactory $exceptionFactory,
    ): Response {
        $this->entityAccessChecker->denyAccessUnlessGranted($source);

        try {
            if ($request instanceof FileSourceRequest && $source instanceof FileSource) {
                $source = $mutator->updateFile($source, $request);
            }

            if ($request instanceof GitSourceRequest && $source instanceof GitSource) {
                $source = $mutator->updateGit($source, $request);
            }
        } catch (NonUniqueEntityLabelException) {
            throw $exceptionFactory->createInvalidRequestExceptionForNonUniqueEntityLabel(
                $request,
                $request->label,
                'source'
            );
        }

        return new JsonResponse($source);
    }

    /**
     * @throws AccessDeniedException
     * @throws FilesystemException
     */
    #[Route(SourceRoutes::ROUTE_SOURCE, name: 'user_source_delete', methods: ['DELETE'])]
    public function delete(
        SourceInterface $source,
        FilesystemWriter $fileSourceWriter,
        FilesystemWriter $runSourceWriter,
    ): Response {
        $this->entityAccessChecker->denyAccessUnlessGranted($source);

        $this->repository->delete($source);

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
