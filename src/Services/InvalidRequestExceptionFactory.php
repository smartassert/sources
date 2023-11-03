<?php

declare(strict_types=1);

namespace App\Services;

use App\Exception\InvalidRequestException;
use App\Request\LabelledObjectRequestInterface;
use App\Request\ObjectRequestInterface;
use App\ResponseBody\InvalidField;

class InvalidRequestExceptionFactory
{
    public function createFromLabelledObjectRequest(
        LabelledObjectRequestInterface&ObjectRequestInterface $request
    ): InvalidRequestException {
        return new InvalidRequestException($request, new InvalidField(
            'label',
            $request->getLabel(),
            sprintf('This label is being used by another %s belonging to this user', $request->getObjectType())
        ));
    }
}
