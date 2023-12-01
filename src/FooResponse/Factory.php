<?php

declare(strict_types=1);

namespace App\FooResponse;

use App\Exception\HasHttpErrorCodeInterface;
use App\FooResponse\ErrorSerializer\Serializer;
use Symfony\Component\HttpFoundation\JsonResponse;

readonly class Factory
{
    public function __construct(
        private Serializer $serializer,
    ) {
    }

    public function create(ErrorInterface $error): JsonResponse
    {
        $statusCode = $error instanceof HasHttpErrorCodeInterface ? $error->getErrorCode() : 400;

        return new JsonResponse($this->serializer->create($error), $statusCode);
    }
}
