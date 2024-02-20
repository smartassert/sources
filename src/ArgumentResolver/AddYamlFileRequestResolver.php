<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Request\AddYamlFileRequest;
use App\RequestParameter\Factory;
use App\RequestParameter\Validator\YamlFilenameParameterValidator;
use App\RequestParameter\Validator\YamlParameterValidator;
use SmartAssert\ServiceRequest\Exception\ErrorResponseException;
use SmartAssert\ServiceRequest\Parameter\Parameter;
use SmartAssert\ServiceRequest\Parameter\Requirements;
use SmartAssert\YamlFile\Filename;
use SmartAssert\YamlFile\YamlFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

readonly class AddYamlFileRequestResolver implements ValueResolverInterface
{
    public const string KEY_ATTRIBUTE_FILENAME = 'filename';

    public function __construct(
        private YamlFilenameParameterValidator $yamlFilenameParameterValidator,
        private YamlParameterValidator $yamlParameterValidator,
        private Factory $parameterFactory,
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

        $filename = $this->yamlFilenameParameterValidator->validate(
            $this->parameterFactory->createYamlFilenameParameter(
                self::KEY_ATTRIBUTE_FILENAME,
                (string) $this->createFilenameFromRequest($request)
            )
        );

        $content = $this->yamlParameterValidator->validate(
            (new Parameter('content', trim($request->getContent())))
                ->withRequirements(new Requirements('yaml'))
        );

        return [new AddYamlFileRequest(new YamlFile($filename, $content))];
    }

    private function createFilenameFromRequest(Request $request): Filename
    {
        $filename = $request->attributes->get(self::KEY_ATTRIBUTE_FILENAME);
        $filename = is_scalar($filename) ? (string) $filename : '';

        return Filename::parse($filename);
    }
}
