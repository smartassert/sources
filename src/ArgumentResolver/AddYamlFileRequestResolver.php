<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Exception\InvalidRequestException;
use App\FooRequest\Field\Field;
use App\FooRequest\Field\Requirements;
use App\FooRequest\Field\YamlFilenameField;
use App\FooRequest\YamlFieldValidator;
use App\FooRequest\YamlFilenameFieldValidator;
use App\Request\AddYamlFileRequest;
use SmartAssert\YamlFile\YamlFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class AddYamlFileRequestResolver extends AbstractYamlFileRequestResolver implements ValueResolverInterface
{
    public function __construct(
        private readonly YamlFilenameFieldValidator $yamlFilenameFieldValidator,
        private readonly YamlFieldValidator $yamlFieldValidator,
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

        $filename = $this->yamlFilenameFieldValidator->validate(new YamlFilenameField(
            self::KEY_ATTRIBUTE_FILENAME,
            $this->createFilenameFromRequest($request)
        ));

        $contentField = new Field(
            'content',
            trim($request->getContent()),
            new Requirements('yaml')
        );

        $content = $this->yamlFieldValidator->validate($contentField);

        return [new AddYamlFileRequest(new YamlFile($filename, $content))];
    }
}
