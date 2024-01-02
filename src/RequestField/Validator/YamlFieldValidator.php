<?php

declare(strict_types=1);

namespace App\RequestField\Validator;

use App\ErrorResponse\BadRequestError;
use App\Exception\BadRequestException;
use SmartAssert\ServiceRequest\Field\FieldInterface;
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
     * @throws BadRequestException
     */
    public function validate(FieldInterface $field): string
    {
        $content = $field->getValue();
        if (!is_string($content)) {
            throw new BadRequestException(new BadRequestError($field, 'wrong_type'));
        }

        if ('' === $content) {
            throw new BadRequestException(new BadRequestError($field, 'empty'));
        }

        $validation = $this->yamlContentValidator->validate($content);
        if (false === $validation->isValid()) {
            throw new BadRequestException(new BadRequestError($field, 'invalid'));
        }

        return $content;
    }
}
