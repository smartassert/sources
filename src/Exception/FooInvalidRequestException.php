<?php

declare(strict_types=1);

namespace App\Exception;

use App\FooResponse\ErrorInterface;
use App\FooResponse\FieldInterface;
use App\FooResponse\RequirementsInterface;

class FooInvalidRequestException extends \Exception implements ErrorInterface, HasHttpErrorCodeInterface
{
    /**
     * @param non-empty-string  $class
     * @param ?non-empty-string $type
     */
    public function __construct(
        private readonly string $class,
        private readonly FieldInterface $field,
        private readonly ?string $type,
        private readonly ?RequirementsInterface $requirements,
    ) {
        $message = $class . ': ' . $class;
        if (is_string($type)) {
            $message .= ' ' . $type;
        }

        parent::__construct($message, 400);
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function getField(): FieldInterface
    {
        return $this->field;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getRequirements(): ?RequirementsInterface
    {
        return $this->requirements;
    }

    public function getErrorCode(): int
    {
        return $this->getCode();
    }
}
