<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Exception\BadRequestException;
use App\Request\YamlFileRequest;
use App\RequestField\Field\YamlFilenameField;
use App\RequestField\Validator\YamlFilenameFieldValidator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class YamlFileRequestResolver extends AbstractYamlFileRequestResolver implements ValueResolverInterface
{
    public function __construct(
        private readonly YamlFilenameFieldValidator $yamlFilenameFieldValidator,
    ) {
    }

    /**
     * @return YamlFileRequest[]
     *
     * @throws BadRequestException
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (YamlFileRequest::class !== $argument->getType()) {
            return [];
        }

        $field = new YamlFilenameField(
            self::KEY_ATTRIBUTE_FILENAME,
            (string) $this->createFilenameFromRequest($request)
        );
        $filename = $this->yamlFilenameFieldValidator->validate($field);

        return [new YamlFileRequest($filename)];
    }
}
