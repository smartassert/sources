<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\EmptyEntityIdException;
use App\Exception\InvalidRequestException;
use App\Exception\NonUniqueEntityLabelException;
use App\Request\FileSourceRequest;
use App\Services\ExceptionFactory;
use App\Services\Source\FileSourceFactory;
use SmartAssert\UsersSecurityBundle\Security\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/file-source', name: 'file_source_')]
readonly class FileSourceController
{
    public function __construct(
        private ExceptionFactory $exceptionFactory,
        private FileSourceFactory $sourceFactory,
    ) {
    }

    /**
     * @throws EmptyEntityIdException
     * @throws InvalidRequestException
     */
    #[Route(name: 'create', methods: ['POST'])]
    public function create(User $user, FileSourceRequest $request): JsonResponse
    {
        try {
            return new JsonResponse($this->sourceFactory->create($user, $request));
        } catch (NonUniqueEntityLabelException) {
            throw $this->exceptionFactory->createInvalidRequestExceptionForNonUniqueEntityLabel(
                $request,
                $request->label,
                'source'
            );
        }
    }
}
