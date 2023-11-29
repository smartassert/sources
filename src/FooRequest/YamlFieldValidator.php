<?php

declare(strict_types=1);

namespace App\FooRequest;

use App\Exception\FooInvalidRequestException;
use App\FooRequest\Field\Field;
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
     * @throws FooInvalidRequestException
     */
    public function validate(Field $field): string
    {
        $content = $field->getValue();
        if ('' === $content) {
            throw new FooInvalidRequestException('invalid_request_field', $field, 'empty');
        }

        $validation = $this->yamlContentValidator->validate($content);
        if (false === $validation->isValid()) {
            throw new FooInvalidRequestException('invalid_request_field', $field, 'invalid');
        }

        return $content;
    }
}
