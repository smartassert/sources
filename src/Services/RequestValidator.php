<?php

declare(strict_types=1);

namespace App\Services;

use App\Exception\InvalidRequestException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RequestValidator
{
    public function __construct(
        private ValidatorInterface $validator,
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
            throw new InvalidRequestException($request, $errors, $propertyNamePrefixesToRemove);
        }
    }
}
