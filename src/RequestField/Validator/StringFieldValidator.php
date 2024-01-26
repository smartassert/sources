<?php

declare(strict_types=1);

namespace App\RequestField\Validator;

use App\Exception\ErrorResponseException;
use App\Exception\ErrorResponseExceptionFactory;
use SmartAssert\ServiceRequest\Parameter\ParameterInterface;
use SmartAssert\ServiceRequest\Parameter\RequirementsInterface;
use SmartAssert\ServiceRequest\Parameter\SizeInterface;

readonly class StringFieldValidator
{
    public function __construct(
        private ErrorResponseExceptionFactory $exceptionFactory,
    ) {
    }

    /**
     * @throws ErrorResponseException
     */
    public function validateString(ParameterInterface $parameter): string
    {
        $value = $parameter->getValue();
        if (!is_string($value)) {
            throw $this->exceptionFactory->createForBadRequest($parameter, 'wrong_type');
        }

        $requirements = $parameter->getRequirements();

        if ($requirements instanceof RequirementsInterface) {
            $sizeRequirements = $requirements->getSize();

            if ($sizeRequirements instanceof SizeInterface) {
                if (is_string($value) && mb_strlen($value) > $sizeRequirements->getMaximum()) {
                    throw $this->exceptionFactory->createForBadRequest($parameter, 'too_large');
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
    public function validateNonEmptyString(ParameterInterface $parameter): string
    {
        $value = $this->validateString($parameter);

        if ('' === $value) {
            throw $this->exceptionFactory->createForBadRequest($parameter, 'empty');
        }

        return $value;
    }
}
