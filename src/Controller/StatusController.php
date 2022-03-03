<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class StatusController
{
    public function __construct(
        private bool $isReady,
    ) {
    }

    #[Route('/status', name: 'status', methods: ['GET'])]
    public function get(): JsonResponse
    {
        return new JsonResponse([
            'idle' => true,
            'ready' => $this->isReady,
        ]);
    }
}
