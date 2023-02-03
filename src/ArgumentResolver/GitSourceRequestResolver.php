<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Request\GitSourceRequest;
use App\Request\SourceRequestInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class GitSourceRequestResolver implements ValueResolverInterface
{
    /**
     * @return SourceRequestInterface[]
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (GitSourceRequest::class !== $argument->getType()) {
            return [];
        }

//        var_dump($request->request);
//        exit();

        return [new GitSourceRequest($request)];
    }
}
