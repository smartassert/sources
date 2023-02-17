<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Controller\SourceRoutes;
use App\Exception\InvalidRequestException;
use App\Request\CreateSuiteRequest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class CreateSuiteRequestResolver extends AbstractSuiteRequestResolver implements ValueResolverInterface
{
    /**
     * @return CreateSuiteRequest[]
     *
     * @throws InvalidRequestException
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (CreateSuiteRequest::class !== $argument->getType()) {
            return [];
        }

        $sourceId = $request->attributes->get(SourceRoutes::ATTRIBUTE_SOURCE_ID);
        $sourceId = is_scalar($sourceId) ? trim((string) $sourceId) : '';
        if ('' === $sourceId) {
            return [];
        }

        return [new CreateSuiteRequest($sourceId, $this->getLabel($request), $this->getTests($request))];
    }
}
