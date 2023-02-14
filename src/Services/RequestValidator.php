<?php

declare(strict_types=1);

namespace App\Services;

use App\Exception\InvalidRequestException;
use App\ResponseBody\InvalidField;
use Symfony\Component\String\UnicodeString;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RequestValidator
{
    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly StringCaseConverter $stringCaseConverter,
    ) {
    }

    /**
     * @param string[] $propertyNamePrefixesToRemove
     *
     * @throws InvalidRequestException
     */
    public function validate(object $request, array $propertyNamePrefixesToRemove = []): void
    {
        $errors = $this->validator->validate($request);
        if (0 !== count($errors)) {
            throw new InvalidRequestException(
                $request,
                $this->createInvalidFieldFromConstraintViolation($errors->get(0), $propertyNamePrefixesToRemove),
                $propertyNamePrefixesToRemove
            );
        }
    }

    /**
     * @param string[] $propertyNamePrefixesToRemove
     */
    private function createInvalidFieldFromConstraintViolation(
        ConstraintViolationInterface $error,
        array $propertyNamePrefixesToRemove
    ): InvalidField {
        $name = $this->stringCaseConverter->convertCamelCaseToKebabCase($error->getPropertyPath());
        $name = (string) (new UnicodeString($name))->trimPrefix($propertyNamePrefixesToRemove);

        $invalidValue = $error->getInvalidValue();
        $invalidValue = is_scalar($invalidValue) ? (string) $invalidValue : '';

        return new InvalidField($name, $invalidValue, (string) $error->getMessage());
    }
}
