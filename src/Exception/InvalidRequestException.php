<?php

declare(strict_types=1);

namespace App\Exception;

use App\ResponseBody\InvalidField;

class InvalidRequestException extends \Exception implements HasHttpErrorCodeInterface
{
    /**
     * @param string[] $propertyNamePrefixesToRemove
     */
    public function __construct(
        private readonly object $request,
        private readonly InvalidField $invalidField,
        private readonly array $propertyNamePrefixesToRemove = []
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

    /**
     * @return string[]
     */
    public function getPropertyNamePrefixesToRemove(): array
    {
        return $this->propertyNamePrefixesToRemove;
    }

    public function getErrorCode(): int
    {
        return 400;
    }
}
