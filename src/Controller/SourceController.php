<?php

declare(strict_types=1);

namespace App\Controller;

use App\Request\CreateSourceRequest;
use App\Services\SourceFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SourceController
{
    #[Route('/{userId<[A-Z90-9]{26}>}', name: 'create', methods: ['POST'])]
    public function create(CreateSourceRequest $request, SourceFactory $sourceFactory): Response
    {
        return new JsonResponse($sourceFactory->createFromRequest($request));
    }
}
