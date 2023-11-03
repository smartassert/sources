<?php

declare(strict_types=1);

namespace App\Services;

use App\Exception\InvalidRequestException;
use App\ResponseBody\InvalidField;

class InvalidRequestExceptionFactory
{
    public function createInvalidRequestExceptionForNonUniqueEntityLabel(
        object $request,
        string $labelValue,
        string $objectType
    ): InvalidRequestException {
        return new InvalidRequestException($request, new InvalidField(
            'label',
            $labelValue,
            sprintf(
                'This label is being used by another %s belonging to this user',
                $objectType
            )
        ));
    }
}
