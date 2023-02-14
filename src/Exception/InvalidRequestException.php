<?php

declare(strict_types=1);

namespace App\Exception;

use Symfony\Component\Validator\ConstraintViolationInterface;

class InvalidRequestException extends \Exception implements HasHttpErrorCodeInterface
{
    /**
     * @param string[] $propertyNamePrefixesToRemove
     */
    public function __construct(
        private object $request,
        private ConstraintViolationInterface $violation,
        private array $propertyNamePrefixesToRemove = []
    ) {
        parent::__construct();
    }

    public function getRequest(): object
    {
        return $this->request;
    }

    public function getViolation(): ConstraintViolationInterface
    {
        return $this->violation;
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
