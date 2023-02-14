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

        return [new GitSourceRequest(
            trim((string) $request->request->get(GitSourceRequest::PARAMETER_LABEL)),
            trim((string) $request->request->get(GitSourceRequest::PARAMETER_HOST_URL)),
            trim((string) $request->request->get(GitSourceRequest::PARAMETER_PATH)),
            trim((string) $request->request->get(GitSourceRequest::PARAMETER_CREDENTIALS)),
        )];
    }
}
