<?php

declare(strict_types=1);

namespace App\ResponseBody;

class InvalidRequestResponse implements ErrorInterface
{
    private const TYPE = 'invalid_request';

    public function __construct(
        private readonly InvalidField $invalidField,
    ) {
    }

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getPayload(): array
    {
        return [
            'name' => $this->invalidField->name,
            'value' => $this->invalidField->value,
            'message' => $this->invalidField->message,
        ];
    }
}
