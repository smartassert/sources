<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Request\YamlFileRequest;
use App\RequestParameter\Factory;
use App\RequestParameter\Validator\YamlFilenameParameterValidator;
use SmartAssert\ServiceRequest\Exception\ErrorResponseException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class YamlFileRequestResolver extends AbstractYamlFileRequestResolver implements ValueResolverInterface
{
    public function __construct(
        private readonly YamlFilenameParameterValidator $yamlFilenameParameterValidator,
        private readonly Factory $parameterFactory,
    ) {
    }

    /**
     * @return YamlFileRequest[]
     *
     * @throws ErrorResponseException
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (YamlFileRequest::class !== $argument->getType()) {
            return [];
        }

        $filename = $this->yamlFilenameParameterValidator->validate(
            $this->parameterFactory->createYamlFilenameParameter(
                self::KEY_ATTRIBUTE_FILENAME,
                (string) $this->createFilenameFromRequest($request)
            )
        );

        return [new YamlFileRequest($filename)];
    }
}
