<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Exception\InvalidRequestException;
use App\Request\GitSourceRequest;
use App\RequestFactory\GitSourceRequestFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class GitSourceRequestResolver implements ValueResolverInterface
{
    public function __construct(
        private readonly GitSourceRequestFactory $gitSourceRequestFactory,
    ) {
    }

    /**
     * @return GitSourceRequest[]
     *
     * @throws InvalidRequestException
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (GitSourceRequest::class !== $argument->getType()) {
            return [];
        }

        return [$this->gitSourceRequestFactory->create($request)];
    }
}
