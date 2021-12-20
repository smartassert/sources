<?php

declare(strict_types=1);

namespace App\Controller;

use App\Request\CreateGitSourceRequest;
use App\Services\SourceFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class SourceController
{
    public const ROUTE_GIT_SOURCE_CREATE = '/git';

    #[Route(self::ROUTE_GIT_SOURCE_CREATE, name: 'create_git', methods: ['POST'])]
    public function createGitSource(
        UserInterface $user,
        CreateGitSourceRequest $request,
        SourceFactory $sourceFactory
    ): JsonResponse {
        return new JsonResponse($sourceFactory->createGitSourceFromRequest($user, $request));
    }
}
