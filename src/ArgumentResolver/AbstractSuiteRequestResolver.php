<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Entity\Suite;
use App\Exception\InvalidRequestException;
use App\Request\SuiteRequest;
use App\ResponseBody\InvalidField;
use SmartAssert\YamlFile\Filename;
use SmartAssert\YamlFile\Validator\YamlFilenameValidator;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractSuiteRequestResolver
{
    public function __construct(
        private readonly YamlFilenameValidator $yamlFilenameValidator,
    ) {
    }

    /**
     * @return non-empty-string
     *
     * @throws InvalidRequestException
     */
    protected function getLabel(Request $request): string
    {
        $label = trim((string) $request->request->get(SuiteRequest::PARAMETER_LABEL));
        if ('' === $label || mb_strlen($label) > Suite::LABEL_MAX_LENGTH) {
            $message = sprintf(
                'This value should be between 1 and %d characters long.',
                Suite::LABEL_MAX_LENGTH
            );

            throw new InvalidRequestException($request, new InvalidField('label', $label, $message));
        }

        return $label;
    }

    /**
     * @return non-empty-string[]
     *
     * @throws InvalidRequestException
     */
    protected function getTests(Request $request): array
    {
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

        return $tests;
    }
}
