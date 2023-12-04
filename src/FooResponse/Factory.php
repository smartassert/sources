<?php

declare(strict_types=1);

namespace App\FooResponse;

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
        $statusCode = $error instanceof HasHttpStatusCodeInterface ? $error->getStatusCode() : 400;

        return new JsonResponse($this->serializer->create($error), $statusCode);
    }
}
