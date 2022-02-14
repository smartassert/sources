<?php

declare(strict_types=1);

namespace App\Controller;

use App\Enum\Source\Type;
use App\Exception\InvalidRequestException;
use App\Repository\SourceRepository;
use App\Request\SourceRequestInterface;
use App\Services\RequestValidator;
use App\Services\Source\Factory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class SourceController
{
    /**
     * @throws InvalidRequestException
     */
    #[Route('/', name: 'source_create', methods: ['POST'])]
    public function create(
        RequestValidator $requestValidator,
        UserInterface $user,
        Factory $factory,
        SourceRequestInterface $request
    ): JsonResponse {
        $requestValidator->validate($request);

        return new JsonResponse($factory->createFromSourceRequest($user, $request));
    }

    #[Route('/list', name: 'source_list', methods: ['GET'])]
    public function list(UserInterface $user, SourceRepository $repository): JsonResponse
    {
        return new JsonResponse($repository->findByUserAndType($user, [Type::FILE, Type::GIT]));
    }
}
