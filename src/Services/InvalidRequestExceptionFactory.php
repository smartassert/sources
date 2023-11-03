<?php

declare(strict_types=1);

namespace App\Services;

use App\Exception\InvalidRequestException;
use App\Exception\NonUniqueEntityLabelException;
use App\ResponseBody\InvalidField;

class InvalidRequestExceptionFactory
{
    public function createFromLabelledObjectRequest(NonUniqueEntityLabelException $exception): InvalidRequestException
    {
        $request = $exception->request;

        return new InvalidRequestException($exception->request, new InvalidField(
            'label',
            $request->getLabel(),
            sprintf('This label is being used by another %s belonging to this user', $request->getObjectType())
        ));
    }
}
