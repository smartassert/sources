<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\FileSource;
use App\Entity\SourceInterface;
use App\Enum\Source\Type;
use App\Exception\ErrorResponseException;
use App\Exception\ErrorResponseExceptionFactory;
use App\Exception\StorageExceptionFactory;
use App\Repository\SourceRepository;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemWriter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;

readonly class SourceController
{
    public function __construct(
        private SourceRepository $repository,
    ) {
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
     * @throws ErrorResponseException
     */
    #[Route(SourceRoutes::ROUTE_SOURCE, name: 'user_source_delete', methods: ['DELETE'])]
    public function delete(
        SourceInterface $source,
        FilesystemWriter $fileSourceWriter,
        ErrorResponseExceptionFactory $exceptionFactory,
        StorageExceptionFactory $storageExceptionFactory,
    ): Response {
        if (null === $source->getDeletedAt()) {
            $this->repository->delete($source);

            if ($source instanceof FileSource) {
                try {
                    $fileSourceWriter->deleteDirectory($source->getDirectoryPath());
                } catch (FilesystemException $e) {
                    throw $exceptionFactory->createForStorageError(
                        $storageExceptionFactory->createForEntityStorageFailure($source, $e)
                    );
                }
            }
        }

        return new JsonResponse($source);
    }
}
