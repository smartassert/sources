<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\FileSource;
use App\Enum\Source\Type;
use App\Exception\InvalidRequestException;
use App\Repository\FileSourceRepository;
use App\Repository\SourceRepository;
use App\Request\FileSourceRequest;
use App\Request\GitSourceRequest;
use App\ResponseBody\InvalidField;
use App\ResponseBody\InvalidRequestResponse;
use App\Services\RequestValidator;
use App\Services\ResponseFactory;
use App\Services\Source\Factory;
use SmartAssert\UsersSecurityBundle\Security\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class SourceController
{
    /**
     * @throws InvalidRequestException
     */
    #[Route('/git', name: 'git_source_create', methods: ['POST'])]
    public function createGitSource(
        RequestValidator $requestValidator,
        User $user,
        Factory $factory,
        GitSourceRequest $request
    ): JsonResponse {
        $requestValidator->validate($request);

        return new JsonResponse($factory->createFromGitSourceRequest($user, $request));
    }

    /**
     * @throws InvalidRequestException
     */
    #[Route('/file', name: 'file_source_create', methods: ['POST'])]
    public function createFileSource(
        RequestValidator $requestValidator,
        User $user,
        Factory $factory,
        FileSourceRepository $repository,
        ResponseFactory $responseFactory,
        FileSourceRequest $request
    ): JsonResponse {
        $requestValidator->validate($request);

        $existingFileSource = $repository->findOneFileSourceByUserAndLabel($user, $request->getLabel());
        if ($existingFileSource instanceof FileSource) {
            return $responseFactory->createErrorResponse(
                new InvalidRequestResponse([
                    new InvalidField(
                        'label',
                        $request->getLabel(),
                        'The label must be unique to this user.'
                    ),
                ]),
                400
            );
        }

        return new JsonResponse($factory->createFromFileSourceRequest($user, $request));
    }

    #[Route('/list', name: 'source_list', methods: ['GET'])]
    public function list(UserInterface $user, SourceRepository $repository): JsonResponse
    {
        return new JsonResponse($repository->findByUserAndType($user, [Type::FILE, Type::GIT]));
    }
}
