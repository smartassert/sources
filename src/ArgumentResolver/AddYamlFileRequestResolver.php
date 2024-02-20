<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Request\AddYamlFileRequest;
use SmartAssert\YamlFile\Filename as YamlFilename;
use SmartAssert\YamlFile\YamlFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

readonly class AddYamlFileRequestResolver implements ValueResolverInterface
{
    /**
     * @return AddYamlFileRequest[]
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (AddYamlFileRequest::class !== $argument->getType()) {
            return [];
        }

        return [new AddYamlFileRequest(new YamlFile(
            YamlFilename::parse($request->attributes->getString('filename')),
            trim($request->getContent())
        ))];
    }
}
