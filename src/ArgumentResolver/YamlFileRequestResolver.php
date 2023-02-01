<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Request\YamlFileRequest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class YamlFileRequestResolver extends AbstractYamlFileRequestResolver implements ArgumentValueResolverInterface
{
    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        return YamlFileRequest::class === $argument->getType();
    }

    /**
     * @return YamlFileRequest[]
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (!$this->supports($request, $argument)) {
            return [];
        }

        return [new YamlFileRequest($this->createFilenameFromRequest($request))];
    }
}
