<?php

declare(strict_types=1);

namespace App\Controller;

use App\Request\CreateSourceRequest;
use App\Services\GitSourceFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class SourceController
{
    #[Route('/', name: 'create', methods: ['POST'])]
    public function create(UserInterface $user, CreateSourceRequest $request, GitSourceFactory $sourceFactory): Response
    {
        return new JsonResponse($sourceFactory->createFromRequest($user, $request));
    }
}
