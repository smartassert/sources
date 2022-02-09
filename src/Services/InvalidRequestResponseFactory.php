<?php

declare(strict_types=1);

namespace App\Services;

use _PHPStan_70b6e53dc\Symfony\Component\String\UnicodeString;
use App\ResponseBody\InvalidField;
use App\ResponseBody\InvalidRequestResponse;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class InvalidRequestResponseFactory
{
    public function __construct(
        private StringCaseConverter $stringCaseConverter,
    ) {
    }

    public function createFromConstraintViolations(
        ConstraintViolationListInterface $errors,
        string $propertyNamePrefixToRemove = ''
    ): InvalidRequestResponse {
        $invalidFields = [];

        foreach ($errors as $error) {
            $invalidValue = $error->getInvalidValue();
            $invalidValue = is_scalar($invalidValue) ? (string) $invalidValue : '';

            $requestField = $this->stringCaseConverter->convertCamelCaseToKebabCase($error->getPropertyPath());
            $requestField = (string) (new UnicodeString($requestField))->trimPrefix($propertyNamePrefixToRemove);

            $invalidFields[] = new InvalidField($requestField, $invalidValue, (string) $error->getMessage());
        }

        return new InvalidRequestResponse($invalidFields);
    }
}
