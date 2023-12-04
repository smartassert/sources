<?php

declare(strict_types=1);

namespace App\RequestField\Validator;

use App\Exception\BadRequestException;
use App\RequestField\ScalarRequirementsInterface;
use App\RequestField\SizeInterface;
use App\RequestField\StringFieldInterface;

class StringFieldValidator
{
    /**
     * @throws BadRequestException
     */
    public function validateString(StringFieldInterface $field): string
    {
        $value = $field->getValue();
        $requirements = $field->getRequirements();
        if ($requirements instanceof ScalarRequirementsInterface) {
            $sizeRequirements = $requirements->getSize();

            if ($sizeRequirements instanceof SizeInterface) {
                if (mb_strlen($value) > $sizeRequirements->getMaximum()) {
                    throw new BadRequestException($field, 'too_large');
                }
            }
        }

        return $value;
    }

    /**
     * @return non-empty-string
     *
     * @throws BadRequestException
     */
    public function validateNonEmptyString(StringFieldInterface $field): string
    {
        $value = $this->validateString($field);

        if ('' === $value) {
            throw new BadRequestException($field, 'empty');
        }

        return $value;
    }
}
