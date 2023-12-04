<?php

declare(strict_types=1);

namespace App\RequestField\Validator;

use App\Exception\InvalidRequestException;
use App\RequestField\Field\Field;
use SmartAssert\YamlFile\Validator\ContentValidator;

readonly class YamlFieldValidator
{
    public function __construct(
        private ContentValidator $yamlContentValidator,
    ) {
    }

    /**
     * @return non-empty-string
     *
     * @throws InvalidRequestException
     */
    public function validate(Field $field): string
    {
        $content = $field->getValue();
        if ('' === $content) {
            throw new InvalidRequestException('invalid_request_field', $field, 'empty');
        }

        $validation = $this->yamlContentValidator->validate($content);
        if (false === $validation->isValid()) {
            throw new InvalidRequestException('invalid_request_field', $field, 'invalid');
        }

        return $content;
    }
}
