<?php

declare(strict_types=1);

namespace App\Exception;

use Symfony\Component\Validator\ConstraintViolationListInterface;

class InvalidRequestException extends \Exception implements HasHttpErrorCodeInterface
{
    /**
     * @param string[] $propertyNamePrefixesToRemove
     */
    public function __construct(
        private object $request,
        private ConstraintViolationListInterface $violations,
        private array $propertyNamePrefixesToRemove = []
    ) {
        parent::__construct();
    }

    public function getRequest(): object
    {
        return $this->request;
    }

    public function getViolations(): ConstraintViolationListInterface
    {
        return $this->violations;
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
