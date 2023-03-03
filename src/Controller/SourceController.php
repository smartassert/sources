<?php

declare(strict_types=1);

namespace App\Controller;

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
use SmartAssert\UsersSecurityBundle\Security\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class SourceController
{
    public function __construct(
        private readonly FileSourceFactory $fileSourceFactory,
        private readonly GitSourceFactory $gitSourceFactory,
        private readonly SourceRepository $repository,
        private readonly ExceptionFactory $exceptionFactory,
    ) {
    }

    /**
     * @throws EmptyEntityIdException
     * @throws InvalidRequestException
     */
    #[Route('/source', name: 'source_create', methods: ['POST'])]
    public function create(User $user, FileSourceRequest|GitSourceRequest $request): JsonResponse
    {
        try {
            if ($request instanceof FileSourceRequest) {
                return new JsonResponse($this->fileSourceFactory->create($user, $request));
            }

            return new JsonResponse($this->gitSourceFactory->create($user, $request));
        } catch (NonUniqueEntityLabelException) {
            throw $this->exceptionFactory->createInvalidRequestExceptionForNonUniqueEntityLabel(
                $request,
                $request->label,
                'source'
            );
        }
    }

    #[Route('/list', name: 'source_list', methods: ['GET'])]
    public function list(UserInterface $user): JsonResponse
    {
        return new JsonResponse($this->repository->findNonDeletedByUserAndType($user, [Type::FILE, Type::GIT]));
    }
}
