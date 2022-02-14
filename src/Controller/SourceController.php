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
    public function __construct(
        private RequestValidator $requestValidator,
    ) {
    }

    /**
     * @throws InvalidRequestException
     */
    #[Route('/', name: 'create', methods: ['POST'])]
    public function create(UserInterface $user, Factory $factory, SourceRequestInterface $request): JsonResponse
    {
        $this->requestValidator->validate($request);

        return new JsonResponse($factory->createFromSourceRequest($user, $request));
    }

    #[Route('/list', name: 'list', methods: ['GET'])]
    public function list(UserInterface $user, SourceRepository $repository): JsonResponse
    {
        return new JsonResponse($repository->findByUserAndType($user, [Type::FILE, Type::GIT]));
    }
}
