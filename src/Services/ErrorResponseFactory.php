<?php

declare(strict_types=1);

namespace App\Services;

use App\Response\ErrorResponseInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class ErrorResponseFactory
{
    public function createResponse(ErrorResponseInterface $error, int $statusCode): JsonResponse
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
