<?php

declare(strict_types=1);

namespace App\Services;

use App\ResponseBody\InvalidField;
use App\ResponseBody\InvalidRequestResponse;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class InvalidRequestResponseFactory
{
    public function __construct(
        private StringCaseConverter $stringCaseConverter,
    ) {
    }

    public function createFromConstraintViolations(ConstraintViolationListInterface $errors): InvalidRequestResponse
    {
        $invalidFields = [];

        foreach ($errors as $error) {
            $invalidValue = $error->getInvalidValue();
            $invalidValue = is_scalar($invalidValue) ? (string) $invalidValue : '';

            $invalidFields[] = new InvalidField(
                $this->stringCaseConverter->convertCamelCaseToKebabCase($error->getPropertyPath()),
                $invalidValue,
                (string) $error->getMessage()
            );
        }

        return new InvalidRequestResponse($invalidFields);
    }
}
