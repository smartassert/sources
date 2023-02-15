<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Controller\SourceRoutes;
use App\Entity\Suite;
use App\Exception\InvalidRequestException;
use App\Request\SuiteRequest;
use App\ResponseBody\InvalidField;
use SmartAssert\YamlFile\Filename;
use SmartAssert\YamlFile\Validator\YamlFilenameValidator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class SuiteRequestResolver implements ValueResolverInterface
{
    public function __construct(
        private readonly YamlFilenameValidator $yamlFilenameValidator,
    ) {
    }

    /**
     * @return SuiteRequest[]
     *
     * @throws InvalidRequestException
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (SuiteRequest::class !== $argument->getType()) {
            return [];
        }

        $sourceId = $request->attributes->get(SourceRoutes::ATTRIBUTE_SOURCE_ID);
        $sourceId = is_scalar($sourceId) ? trim((string) $sourceId) : '';
        if ('' === $sourceId) {
            return [];
        }

        $label = trim((string) $request->request->get(SuiteRequest::PARAMETER_LABEL));
        if ('' === $label || mb_strlen($label) > Suite::LABEL_MAX_LENGTH) {
            $message = sprintf(
                'This value should be between 1 and %d characters long.',
                Suite::LABEL_MAX_LENGTH
            );

            throw new InvalidRequestException($request, new InvalidField('label', $label, $message));
        }

        $requestTests = $request->request->all(SuiteRequest::PARAMETER_TESTS);
        $tests = [];

        foreach ($requestTests as $requestTest) {
            if (is_string($requestTest)) {
                $requestTest = trim($requestTest);

                $validation = $this->yamlFilenameValidator->validate(Filename::parse($requestTest));
                if ($validation->isValid() && '' !== $requestTest) {
                    $tests[] = $requestTest;
                } else {
                    throw new InvalidRequestException(
                        $request,
                        new InvalidField(
                            'tests',
                            implode(', ', $requestTests),
                            'Tests must be valid yaml file paths.'
                        )
                    );
                }
            }
        }

        return [new SuiteRequest($sourceId, $label, $tests)];
    }
}
