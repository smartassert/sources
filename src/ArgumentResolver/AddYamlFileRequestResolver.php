<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Exception\ErrorResponseException;
use App\Request\AddYamlFileRequest;
use App\RequestField\Field\Factory;
use App\RequestField\Validator\YamlFieldValidator;
use App\RequestField\Validator\YamlFilenameFieldValidator;
use SmartAssert\ServiceRequest\Parameter\Parameter;
use SmartAssert\ServiceRequest\Parameter\Requirements;
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
     * @throws ErrorResponseException
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (AddYamlFileRequest::class !== $argument->getType()) {
            return [];
        }

        $filename = $this->yamlFilenameFieldValidator->validate($this->fieldFactory->createYamlFilenameParameter(
            self::KEY_ATTRIBUTE_FILENAME,
            (string) $this->createFilenameFromRequest($request)
        ));

        $content = $this->yamlFieldValidator->validate(
            (new Parameter('content', trim($request->getContent())))
                ->withRequirements(new Requirements('yaml'))
        );

        return [new AddYamlFileRequest(new YamlFile($filename, $content))];
    }
}
