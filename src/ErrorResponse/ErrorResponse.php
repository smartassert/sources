<?php

declare(strict_types=1);

namespace App\ErrorResponse;

class ErrorResponse implements FooErrorInterface
{
    /**
     * @param non-empty-string  $class
     * @param ?non-empty-string $type
     */
    public function __construct(
        private readonly string $class,
        private readonly ?string $type,
    ) {
    }

    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * @return ?non-empty-string
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    public function serialize(): array
    {
        $data = ['class' => $this->class];

        if (is_string($this->type)) {
            $data['type'] = $this->type;
        }

        return $data;
    }
}
