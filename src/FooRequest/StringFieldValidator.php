<?php

declare(strict_types=1);

namespace App\FooRequest;

use App\Exception\FooInvalidRequestException;
use App\FooResponse\SizeInterface;

class StringFieldValidator
{
    /**
     * @throws FooInvalidRequestException
     */
    public function validateString(StringFieldInterface $field): string
    {
        $value = $field->getValue();
        $requirements = $field->getRequirements();
        if ($requirements instanceof ScalarRequirementsInterface) {
            $sizeRequirements = $requirements->getSize();

            if ($sizeRequirements instanceof SizeInterface) {
                if (mb_strlen($value) > $sizeRequirements->getMaximum()) {
                    throw new FooInvalidRequestException('invalid_request_field', $field, 'too_large');
                }
            }
        }

        return $value;
    }

    /**
     * @return non-empty-string
     *
     * @throws FooInvalidRequestException
     */
    public function validateNonEmptyString(StringFieldInterface $field): string
    {
        $value = $this->validateString($field);

        if ('' === $value) {
            throw new FooInvalidRequestException('invalid_request_field', $field, 'empty');
        }

        return $value;
    }
}
