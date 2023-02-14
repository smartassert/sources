<?php

declare(strict_types=1);

namespace App\RequestValidator;

use App\Exception\InvalidRequestException;
use App\ResponseBody\InvalidField;

class ValueLengthValidator
{
    /**
     * @throws InvalidRequestException
     */
    public function validate(object $request, string $name, string $value, int $minimumLength, int $maximumLength): void
    {
        $length = mb_strlen($value);

        if ($length <= $minimumLength || $length > $maximumLength) {
            throw new InvalidRequestException(
                $request,
                new InvalidField(
                    $name,
                    $value,
                    sprintf(
                        'This value should be between %d and %d characters long.',
                        $minimumLength,
                        $maximumLength
                    ),
                ),
            );
        }
    }
}
