<?php

declare(strict_types=1);

namespace App\RequestField\Validator;

use App\ErrorResponse\BadRequestError;
use App\Exception\BadRequestException;
use App\RequestField\FieldInterface;
use App\RequestField\RequirementsInterface;
use App\RequestField\SizeInterface;

class StringFieldValidator
{
    /**
     * @throws BadRequestException
     */
    public function validateString(FieldInterface $field): string
    {
        $value = $field->getValue();
        if (!is_string($value)) {
            throw new BadRequestException(new BadRequestError($field, 'wrong_type'));
        }

        $requirements = $field->getRequirements();

        if ($requirements instanceof RequirementsInterface) {
            $sizeRequirements = $requirements->getSize();

            if ($sizeRequirements instanceof SizeInterface) {
                if (is_string($value) && mb_strlen($value) > $sizeRequirements->getMaximum()) {
                    throw new BadRequestException(new BadRequestError($field, 'too_large'));
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
    public function validateNonEmptyString(FieldInterface $field): string
    {
        $value = $this->validateString($field);

        if ('' === $value) {
            throw new BadRequestException(new BadRequestError($field, 'empty'));
        }

        return $value;
    }
}
