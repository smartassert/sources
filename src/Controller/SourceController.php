<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\FileSource;
use App\Entity\SourceInterface;
use App\Enum\Source\Type;
use App\Exception\EmptyEntityIdException;
use App\Exception\InvalidRequestException;
use App\Exception\NonUniqueEntityLabelException;
use App\Repository\SourceRepository;
use App\Request\FileSourceRequest;
use App\Request\GitSourceRequest;
use App\Services\ExceptionFactory;
use App\Services\Source\FileSourceFactory;
use App\Services\Source\GitSourceFactory;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemWriter;
use SmartAssert\UsersSecurityBundle\Security\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class SourceController
{
    public function __construct(
        private readonly SourceRepository $repository,
        private readonly ExceptionFactory $exceptionFactory,
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

    #[Route(SourceRoutes::ROUTE_SOURCE, name: 'user_source_get', methods: ['GET'])]
    public function get(SourceInterface $source): Response
    {
        return new JsonResponse($source);
    }

    /**
     * @throws FilesystemException
     */
    #[Route(SourceRoutes::ROUTE_SOURCE, name: 'user_source_delete', methods: ['DELETE'])]
    public function delete(SourceInterface $source, FilesystemWriter $fileSourceWriter): Response
    {
        if (null === $source->getDeletedAt()) {
            $this->repository->delete($source);

            if ($source instanceof FileSource) {
                $fileSourceWriter->deleteDirectory($source->getDirectoryPath());
            }
        }

        return new JsonResponse($source);
    }
}
