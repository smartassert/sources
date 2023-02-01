<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Request\AddYamlFileRequest;
use SmartAssert\YamlFile\YamlFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class AddYamlFileRequestResolver extends AbstractYamlFileRequestResolver implements ArgumentValueResolverInterface
{
    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        return AddYamlFileRequest::class === $argument->getType();
    }

    /**
     * @return AddYamlFileRequest[]
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (!$this->supports($request, $argument)) {
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
