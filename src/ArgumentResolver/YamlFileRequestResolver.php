<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Request\YamlFileRequest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class YamlFileRequestResolver extends AbstractYamlFileRequestResolver implements ValueResolverInterface
{
    /**
     * @return YamlFileRequest[]
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (YamlFileRequest::class !== $argument->getType()) {
            return [];
        }

        return [new YamlFileRequest($this->createFilenameFromRequest($request))];
    }
}
