<?php

declare(strict_types=1);

namespace App\Services;

use App\ResponseBody\InvalidSourceRequestResponse;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class InvalidSourceRequestResponseFactory
{
    public function __construct(
        private StringCaseConverter $stringCaseConverter,
    ) {
    }

    public function createFromConstraintViolations(
        string $type,
        ConstraintViolationListInterface $validationErrors
    ): InvalidSourceRequestResponse {
        $missingRequiredFields = [];

        foreach ($validationErrors as $validationError) {
            if ('' === $validationError->getInvalidValue()) {
                $missingRequiredFields[] = $this->stringCaseConverter->convertCamelCaseToKebabCase(
                    $validationError->getPropertyPath()
                );
            }
        }

        return new InvalidSourceRequestResponse($type, $missingRequiredFields);
    }
}
