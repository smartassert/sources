<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Exception\BadRequestException;
use App\Request\AddYamlFileRequest;
use App\RequestField\Field\Factory;
use App\RequestField\Validator\YamlFieldValidator;
use App\RequestField\Validator\YamlFilenameFieldValidator;
use SmartAssert\ServiceRequest\Field\Field;
use SmartAssert\ServiceRequest\Field\Requirements;
use SmartAssert\YamlFile\YamlFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class AddYamlFileRequestResolver extends AbstractYamlFileRequestResolver implements ValueResolverInterface
{
    public function __construct(
        private readonly YamlFilenameFieldValidator $yamlFilenameFieldValidator,
        private readonly YamlFieldValidator $yamlFieldValidator,
        private readonly Factory $fieldFactory,
    ) {
    }

    /**
     * @return AddYamlFileRequest[]
     *
     * @throws BadRequestException
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (AddYamlFileRequest::class !== $argument->getType()) {
            return [];
        }

        $filename = $this->yamlFilenameFieldValidator->validate($this->fieldFactory->createYamlFilenameField(
            self::KEY_ATTRIBUTE_FILENAME,
            (string) $this->createFilenameFromRequest($request)
        ));

        $contentField = (new Field('content', trim($request->getContent())))
            ->withRequirements(new Requirements('yaml'))
        ;

        $content = $this->yamlFieldValidator->validate($contentField);

        return [new AddYamlFileRequest(new YamlFile($filename, $content))];
    }
}
