<?php

declare(strict_types=1);

namespace App\Services;

use App\ResponseBody\InvalidField;
use App\ResponseBody\InvalidRequestResponse;
use Symfony\Component\String\UnicodeString;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class InvalidRequestResponseFactory
{
    public function __construct(
        private StringCaseConverter $stringCaseConverter,
    ) {
    }

    /**
     * @param string[] $propertyNamePrefixesToRemove
     */
    public function createFromConstraintViolations(
        ConstraintViolationListInterface $errors,
        array $propertyNamePrefixesToRemove = []
    ): InvalidRequestResponse {
        $error = $errors->get(0);

        $invalidValue = $error->getInvalidValue();
        $invalidValue = is_scalar($invalidValue) ? (string) $invalidValue : '';

        $requestField = $this->stringCaseConverter->convertCamelCaseToKebabCase($error->getPropertyPath());
        $requestField = (string) (new UnicodeString($requestField))->trimPrefix($propertyNamePrefixesToRemove);

        $invalidField = new InvalidField($requestField, $invalidValue, (string) $error->getMessage());

        return new InvalidRequestResponse($invalidField);
    }
}
