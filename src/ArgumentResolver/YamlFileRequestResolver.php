<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Exception\InvalidRequestException;
use App\Request\YamlFileRequest;
use App\RequestValidator\YamlFileRequestValidator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class YamlFileRequestResolver extends AbstractYamlFileRequestResolver implements ValueResolverInterface
{
    public function __construct(
        private readonly YamlFileRequestValidator $requestValidator,
    ) {
    }

    /**
     * @return YamlFileRequest[]
     *
     * @throws InvalidRequestException
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (YamlFileRequest::class !== $argument->getType()) {
            return [];
        }

        $yamlFileRequest = new YamlFileRequest($this->createFilenameFromRequest($request));
        $this->requestValidator->validate($yamlFileRequest);

        return [$yamlFileRequest];
    }
}
