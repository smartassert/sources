<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Exception\InvalidRequestException;
use App\Request\AddYamlFileRequest;
use App\RequestValidator\AddYamlFileRequestValidator;
use SmartAssert\YamlFile\YamlFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class AddYamlFileRequestResolver extends AbstractYamlFileRequestResolver implements ValueResolverInterface
{
    public function __construct(
        private readonly AddYamlFileRequestValidator $requestValidator,
    ) {
    }

    /**
     * @return AddYamlFileRequest[]
     *
     * @throws InvalidRequestException
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (AddYamlFileRequest::class !== $argument->getType()) {
            return [];
        }

        $addYamlFileRequest = new AddYamlFileRequest(
            new YamlFile(
                $this->createFilenameFromRequest($request),
                trim($request->getContent())
            )
        );

        $this->requestValidator->validate($addYamlFileRequest);

        return [$addYamlFileRequest];
    }
}
