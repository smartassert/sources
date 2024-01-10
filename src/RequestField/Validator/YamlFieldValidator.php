<?php

declare(strict_types=1);

namespace App\RequestField\Validator;

use App\Exception\ErrorResponseException;
use App\Exception\ErrorResponseExceptionFactory;
use SmartAssert\ServiceRequest\Field\FieldInterface;
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
    public function validate(FieldInterface $field): string
    {
        $content = $field->getValue();
        if (!is_string($content)) {
            throw $this->exceptionFactory->createForBadRequest($field, 'wrong_type');
        }

        if ('' === $content) {
            throw $this->exceptionFactory->createForBadRequest($field, 'empty');
        }

        $validation = $this->yamlContentValidator->validate($content);
        if (false === $validation->isValid()) {
            throw $this->exceptionFactory->createForBadRequest($field, 'invalid');
        }

        return $content;
    }
}
