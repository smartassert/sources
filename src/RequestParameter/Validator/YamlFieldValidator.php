<?php

declare(strict_types=1);

namespace App\RequestParameter\Validator;

use App\Exception\ErrorResponseException;
use App\Exception\ErrorResponseExceptionFactory;
use SmartAssert\ServiceRequest\Parameter\ParameterInterface;
use SmartAssert\YamlFile\Validator\ContentValidator;

readonly class YamlFieldValidator
{
    public function __construct(
        private ContentValidator $yamlContentValidator,
        private ErrorResponseExceptionFactory $exceptionFactory,
    ) {
    }

    /**
     * @return non-empty-string
     *
     * @throws ErrorResponseException
     */
    public function validate(ParameterInterface $parameter): string
    {
        $content = $parameter->getValue();
        if (!is_string($content)) {
            throw $this->exceptionFactory->createForBadRequest($parameter, 'wrong_type');
        }

        if ('' === $content) {
            throw $this->exceptionFactory->createForBadRequest($parameter, 'empty');
        }

        $validation = $this->yamlContentValidator->validate($content);
        if (false === $validation->isValid()) {
            throw $this->exceptionFactory->createForBadRequest($parameter, 'invalid');
        }

        return $content;
    }
}
