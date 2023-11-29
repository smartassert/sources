<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Exception\InvalidRequestException;
use App\FooRequest\Field\YamlFilenameField;
use App\FooRequest\YamlFilenameFieldValidator;
use App\Request\YamlFileRequest;
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
     * @throws InvalidRequestException
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (YamlFileRequest::class !== $argument->getType()) {
            return [];
        }

        $field = new YamlFilenameField(self::KEY_ATTRIBUTE_FILENAME, $this->createFilenameFromRequest($request));
        $filename = $this->yamlFilenameFieldValidator->validate($field);

        return [new YamlFileRequest($filename)];
    }
}
