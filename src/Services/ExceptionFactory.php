<?php

declare(strict_types=1);

namespace App\Services;

use App\Exception\InvalidRequestException;
use App\ResponseBody\InvalidField;

class ExceptionFactory
{
    public function createInvalidRequestExceptionForNonUniqueSourceLabel(
        object $request,
        string $label,
        string $sourceType
    ): InvalidRequestException {
        return new InvalidRequestException($request, new InvalidField(
            'label',
            $label,
            sprintf(
                'This label is being used by another %s source belonging to this user',
                $sourceType
            )
        ));
    }
}
