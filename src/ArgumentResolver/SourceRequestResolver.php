<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Enum\Source\Type;
use App\Request\FileSourceRequest;
use App\Request\GitSourceRequest;
use App\Request\InvalidSourceTypeRequest;
use App\Request\SourceRequestInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class SourceRequestResolver implements ValueResolverInterface
{
    /**
     * @return SourceRequestInterface[]
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (SourceRequestInterface::class !== $argument->getType()) {
            return [];
        }

        $sourceTypeParameter = $request->request->get('type');
        $sourceTypeParameter = is_string($sourceTypeParameter) ? trim($sourceTypeParameter) : '';

        try {
            $type = Type::from($sourceTypeParameter);
        } catch (\ValueError) {
            return [new InvalidSourceTypeRequest($sourceTypeParameter)];
        }

        if (Type::FILE === $type) {
            return [new FileSourceRequest($request)];
        }
        if (Type::GIT === $type) {
            return [new GitSourceRequest($request)];
        }

        return [];
    }
}
