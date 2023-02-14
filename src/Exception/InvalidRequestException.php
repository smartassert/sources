<?php

declare(strict_types=1);

namespace App\Exception;

use App\ResponseBody\InvalidField;

class InvalidRequestException extends \Exception implements HasHttpErrorCodeInterface
{
    public function __construct(
        private readonly object $request,
        private readonly InvalidField $invalidField,
    ) {
        parent::__construct();
    }

    public function getRequest(): object
    {
        return $this->request;
    }

    public function getInvalidField(): InvalidField
    {
        return $this->invalidField;
    }

    public function getErrorCode(): int
    {
        return 400;
    }
}
