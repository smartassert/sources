<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Enum\Source\Type;
use App\Request\FileSourceRequest;
use App\Request\GitSourceRequest;
use App\Request\InvalidSourceTypeRequest;
use App\Request\SourceRequestInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class SourceRequestResolver implements ArgumentValueResolverInterface
{
    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        return SourceRequestInterface::class === $argument->getType();
    }

    /**
     * @return iterable<?SourceRequestInterface>
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $sourceTypeParameter = $request->request->get('type');
        $sourceTypeParameter = is_string($sourceTypeParameter) ? trim($sourceTypeParameter) : '';

        $type = Type::FILE;

        try {
            $type = Type::from($sourceTypeParameter);
        } catch (\ValueError) {
            yield new InvalidSourceTypeRequest($sourceTypeParameter);
        }

        if (Type::FILE === $type) {
            yield new FileSourceRequest($request);
        } elseif (Type::GIT === $type) {
            yield new GitSourceRequest($request);
        } else {
            yield null;
        }
    }
}
