<?php

declare(strict_types=1);

namespace App\RequestParameter\Validator;

use SmartAssert\ServiceRequest\Exception\ErrorResponseException;
use SmartAssert\ServiceRequest\Exception\ErrorResponseExceptionFactory;
use SmartAssert\ServiceRequest\Parameter\ParameterInterface;
use SmartAssert\YamlFile\Filename as YamlFilename;
use SmartAssert\YamlFile\Validator\YamlFilenameValidator;

readonly class YamlFilenameParameterValidator
{
    public function __construct(
        private YamlFilenameValidator $yamlFilenameValidator,
        private ErrorResponseExceptionFactory $exceptionFactory,
    ) {
    }

    /**
     * @throws ErrorResponseException
     */
    public function validate(ParameterInterface $parameter): YamlFilename
    {
        $value = $parameter->getValue();
        if (!is_string($value)) {
            throw $this->exceptionFactory->createForBadRequest($parameter, 'wrong_type');
        }

        $filename = YamlFilename::parse($value);
        $validation = $this->yamlFilenameValidator->validate($filename);

        if (false === $validation->isValid()) {
            throw $this->exceptionFactory->createForBadRequest($parameter, 'invalid');
        }

        return $filename;
    }
}
