<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\FileSource;
use App\Exception\DuplicateObjectException;
use App\Exception\EmptyEntityIdException;
use App\Exception\ModifyReadOnlyEntityException;
use App\Exception\StorageException;
use App\Exception\StorageExceptionFactory;
use App\Request\FileSourceRequest;
use App\Services\Source\FileSourceFactory;
use App\Services\Source\Mutator;
use App\Services\SourceRepository\Reader\FileSourceDirectoryLister;
use League\Flysystem\FilesystemException;
use SmartAssert\ServiceRequest\Error\ModifyReadOnlyEntityError;
use SmartAssert\UsersSecurityBundle\Security\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/file-source', name: 'file_source_')]
readonly class FileSourceController
{
    public function __construct(
        private FileSourceFactory $sourceFactory,
        private Mutator $sourceMutator,
    ) {
    }

    /**
     * @throws EmptyEntityIdException
     * @throws DuplicateObjectException
     */
    #[Route(name: 'create', methods: ['POST'])]
    public function create(User $user, FileSourceRequest $request): JsonResponse
    {
        return new JsonResponse($this->sourceFactory->create($user, $request));
    }

    /**
     * @throws ModifyReadOnlyEntityException
     * @throws DuplicateObjectException
     */
    #[Route(path: '/' . SourceRoutes::ROUTE_SOURCE_ID_PATTERN, name: 'update', methods: ['PUT'])]
    public function update(FileSource $source, FileSourceRequest $request): Response
    {
        if (null !== $source->getDeletedAt()) {
            throw new ModifyReadOnlyEntityException(
                new ModifyReadOnlyEntityError(
                    $source->getIdentifier()->getId(),
                    $source->getIdentifier()->getType(),
                )
            );
        }

        return new JsonResponse($this->sourceMutator->updateFile($source, $request));
    }

    /**
     * @throws StorageException
     */
    #[Route(path: '/' . SourceRoutes::ROUTE_SOURCE_ID_PATTERN . '/list/', name: 'list_filenames', methods: ['GET'])]
    public function listFilenames(
        FileSource $source,
        FileSourceDirectoryLister $lister,
        StorageExceptionFactory $exceptionFactory,
    ): Response {
        try {
            return new JsonResponse($lister->list($source));
        } catch (FilesystemException $e) {
            throw $exceptionFactory->createForEntityStorageFailure($source, $e);
        }
    }
}
