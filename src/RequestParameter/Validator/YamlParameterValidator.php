<?php

declare(strict_types=1);

namespace App\RequestParameter\Validator;

use SmartAssert\ServiceRequest\Exception\ErrorResponseException;
use SmartAssert\ServiceRequest\Exception\ErrorResponseExceptionFactory;
use SmartAssert\ServiceRequest\Parameter\ParameterInterface;
use SmartAssert\YamlFile\Validator\ContentValidator;

readonly class YamlParameterValidator
{
    public function __construct(
        private ContentValidator $yamlContentValidator,
        private ErrorResponseExceptionFactory $exceptionFactory,
    ) {
    }

    /**
     * @throws ErrorResponseException
     */
    public function validate(ParameterInterface $parameter): string
    {
        $content = $parameter->getValue();
        if (!is_string($content)) {
            throw $this->exceptionFactory->createForBadRequest($parameter, 'wrong_type');
        }

        $validation = $this->yamlContentValidator->validate($content);
        if (false === $validation->isValid()) {
            throw $this->exceptionFactory->createForBadRequest($parameter, 'invalid');
        }

        return $content;
    }
}
