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
    public function validateString(FieldInterface $field): string
    {
        $value = (string) $field->getValue();

        $sizeRequirements = $field->getRequirements()->getSize();
        if ($sizeRequirements instanceof SizeInterface) {
            if (mb_strlen($value) > $sizeRequirements->getMaximum()) {
                throw new FooInvalidRequestException('invalid_request_field', $field, 'too_large');
            }
        }

        return $value;
    }

    /**
     * @return non-empty-string
     *
     * @throws FooInvalidRequestException
     */
    public function validateNonEmptyString(FieldInterface $field): string
    {
        $value = $this->validateString($field);

        if ('' === $value) {
            throw new FooInvalidRequestException('invalid_request_field', $field, 'empty');
        }

        return $value;
    }
}
