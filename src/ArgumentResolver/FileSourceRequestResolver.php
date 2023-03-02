<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Exception\InvalidRequestException;
use App\Request\FileSourceRequest;
use App\RequestFactory\FileSourceRequestFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class FileSourceRequestResolver implements ValueResolverInterface
{
    public function __construct(
        private readonly FileSourceRequestFactory $fileSourceRequestFactory,
    ) {
    }

    /**
     * @return FileSourceRequest[]
     *
     * @throws InvalidRequestException
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (FileSourceRequest::class !== $argument->getType()) {
            return [];
        }

        return [$this->fileSourceRequestFactory->create($request)];
    }
}
