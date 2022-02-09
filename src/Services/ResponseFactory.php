<?php

declare(strict_types=1);

namespace App\Services;

use App\ResponseBody\ErrorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class ResponseFactory
{
    public function createErrorResponse(ErrorInterface $error, int $statusCode): JsonResponse
    {
        return new JsonResponse(
            [
                'error' => [
                    'type' => $error->getType(),
                    'payload' => $error->getPayload(),
                ],
            ],
            $statusCode
        );
    }
}
