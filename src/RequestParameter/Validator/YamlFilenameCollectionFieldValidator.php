<?php

declare(strict_types=1);

namespace App\RequestParameter\Validator;

use App\Exception\ErrorResponseException;
use App\Exception\ErrorResponseExceptionFactory;
use SmartAssert\ServiceRequest\Parameter\ParameterInterface;
use SmartAssert\YamlFile\Filename;
use SmartAssert\YamlFile\Validator\YamlFilenameValidator;

readonly class YamlFilenameCollectionFieldValidator
{
    public function __construct(
        private YamlFilenameValidator $yamlFilenameValidator,
        private ErrorResponseExceptionFactory $exceptionFactory,
    ) {
    }

    /**
     * @return non-empty-string[]
     *
     * @throws ErrorResponseException
     */
    public function validate(ParameterInterface $parameter): array
    {
        $names = $parameter->getValue();
        if (!is_array($names)) {
            throw $this->exceptionFactory->createForBadRequest($parameter, 'wrong_type');
        }

        $validatedNames = [];

        foreach ($names as $nameIndex => $name) {
            if (is_string($name)) {
                $validation = $this->yamlFilenameValidator->validate(Filename::parse($name));

                if ($validation->isValid() && '' !== $name) {
                    $validatedNames[] = $name;
                } else {
                    $parameter = $parameter->withErrorPosition($nameIndex + 1);

                    throw $this->exceptionFactory->createForBadRequest($parameter, 'invalid');
                }
            }
        }

        return $validatedNames;
    }
}
