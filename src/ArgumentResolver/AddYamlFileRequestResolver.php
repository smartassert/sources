<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Request\AddYamlFileRequest;
use SmartAssert\YamlFile\YamlFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class AddYamlFileRequestResolver extends AbstractYamlFileRequestResolver implements ValueResolverInterface
{
    /**
     * @return AddYamlFileRequest[]
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (AddYamlFileRequest::class !== $argument->getType()) {
            return [];
        }

        return [new AddYamlFileRequest(
            new YamlFile(
                $this->createFilenameFromRequest($request),
                trim($request->getContent())
            )
        )];
    }
}
