<?php

declare(strict_types=1);

namespace App\FooRequest;

use App\Exception\FooInvalidRequestException;
use App\FooResponse\SizeInterface;

class FieldValidator
{
    /**
     * @throws FooInvalidRequestException
     */
    public function validate(FieldInterface $field): void
    {
        $value = $field->getValue();

        if ('' === $value && !$field->getRequirements()->canBeEmpty()) {
            throw new FooInvalidRequestException('invalid_request_field', $field, 'empty');
        }

        $sizeRequirements = $field->getRequirements()->getSize();
        if ($sizeRequirements instanceof SizeInterface) {
            if ($field instanceof StringFieldInterface && is_string($value)) {
                if (mb_strlen($value) > $sizeRequirements->getMaximum()) {
                    throw new FooInvalidRequestException('invalid_request_field', $field, 'too_large');
                }
            }
        }
    }
}
