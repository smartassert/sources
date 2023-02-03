<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Request\FileSourceRequest;
use App\Request\SourceRequestInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class FileSourceRequestResolver implements ValueResolverInterface
{
    /**
     * @return SourceRequestInterface[]
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (FileSourceRequest::class !== $argument->getType()) {
            return [];
        }

        return [new FileSourceRequest($request)];
    }
}
