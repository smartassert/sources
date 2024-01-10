<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Exception\ErrorResponseException;
use App\Request\YamlFileRequest;
use App\RequestField\Field\Factory;
use App\RequestField\Validator\YamlFilenameFieldValidator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class YamlFileRequestResolver extends AbstractYamlFileRequestResolver implements ValueResolverInterface
{
    public function __construct(
        private readonly YamlFilenameFieldValidator $yamlFilenameFieldValidator,
        private readonly Factory $fieldFactory,
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

        $filename = $this->yamlFilenameFieldValidator->validate($this->fieldFactory->createYamlFilenameField(
            self::KEY_ATTRIBUTE_FILENAME,
            (string) $this->createFilenameFromRequest($request)
        ));

        return [new YamlFileRequest($filename)];
    }
}
