<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Request\GitSourceRequest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class GitSourceRequestResolver implements ValueResolverInterface
{
    /**
     * @return GitSourceRequest[]
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (GitSourceRequest::class !== $argument->getType()) {
            return [];
        }

        return [new GitSourceRequest($request)];
    }
}
