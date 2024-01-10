<?php

declare(strict_types=1);

namespace App\RequestField\Validator;

use App\Exception\ErrorResponseException;
use App\Exception\ErrorResponseExceptionFactory;
use SmartAssert\ServiceRequest\Field\FieldInterface;
use SmartAssert\ServiceRequest\Field\RequirementsInterface;
use SmartAssert\ServiceRequest\Field\SizeInterface;

readonly class StringFieldValidator
{
    public function __construct(
        private ErrorResponseExceptionFactory $exceptionFactory,
    ) {
    }

    /**
     * @throws ErrorResponseException
     */
    public function validateString(FieldInterface $field): string
    {
        $value = $field->getValue();
        if (!is_string($value)) {
            throw $this->exceptionFactory->createForBadRequest($field, 'wrong_type');
        }

        $requirements = $field->getRequirements();

        if ($requirements instanceof RequirementsInterface) {
            $sizeRequirements = $requirements->getSize();

            if ($sizeRequirements instanceof SizeInterface) {
                if (is_string($value) && mb_strlen($value) > $sizeRequirements->getMaximum()) {
                    throw $this->exceptionFactory->createForBadRequest($field, 'too_large');
                }
            }
        }

        return $value;
    }

    /**
     * @return non-empty-string
     *
     * @throws ErrorResponseException
     */
    public function validateNonEmptyString(FieldInterface $field): string
    {
        $value = $this->validateString($field);

        if ('' === $value) {
            throw $this->exceptionFactory->createForBadRequest($field, 'empty');
        }

        return $value;
    }
}
