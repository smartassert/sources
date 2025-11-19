<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\FileSource;
use App\Exception\StorageExceptionFactory;
use App\Request\FileSourceRequest;
use App\Services\Source\FileSourceFactory;
use App\Services\Source\Mutator;
use App\Services\SourceRepository\Reader\DirectoryListingFactoryInterface;
use League\Flysystem\FilesystemException;
use SmartAssert\ServiceRequest\Exception\ErrorResponseException;
use SmartAssert\ServiceRequest\Exception\ErrorResponseExceptionFactory;
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
        private ErrorResponseExceptionFactory $exceptionFactory,
    ) {}

    /**
     * @throws ErrorResponseException
     */
    #[Route(name: 'create', methods: ['POST'])]
    public function create(User $user, FileSourceRequest $request): JsonResponse
    {
        return new JsonResponse($this->sourceFactory->create($user, $request));
    }

    /**
     * @throws ErrorResponseException
     */
    #[Route(path: '/' . SourceRoutes::ROUTE_SOURCE_ID_PATTERN, name: 'update', methods: ['PUT'])]
    public function update(FileSource $source, FileSourceRequest $request): Response
    {
        if (null !== $source->getDeletedAt()) {
            throw $this->exceptionFactory->createForModifyReadOnlyEntity(
                $source->getIdentifier()->getId(),
                $source->getIdentifier()->getType(),
            );
        }

        return new JsonResponse($this->sourceMutator->updateFile($source, $request));
    }

    /**
     * @throws ErrorResponseException
     */
    #[Route(path: '/' . SourceRoutes::ROUTE_SOURCE_ID_PATTERN . '/list/', name: 'list', methods: ['GET'])]
    public function list(
        FileSource $source,
        DirectoryListingFactoryInterface $lister,
        StorageExceptionFactory $storageExceptionFactory,
    ): Response {
        try {
            return new JsonResponse($lister->list($source));
        } catch (FilesystemException $e) {
            throw $this->exceptionFactory->createForStorageError(
                $storageExceptionFactory->createForEntityStorageFailure($source, $e)
            );
        }
    }
}
