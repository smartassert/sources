<?php

declare(strict_types=1);

namespace App\ResponseBody;

class InvalidRequestResponse implements ErrorInterface
{
    private const TYPE = 'invalid_request';

    /**
     * @param InvalidField[] $invalidFields
     */
    public function __construct(
        private array $invalidFields,
    ) {
    }

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getPayload(): array
    {
        $payload = [];

        foreach ($this->invalidFields as $invalidField) {
            $payload[$invalidField->name] = [
                'name' => $invalidField->name,
                'value' => $invalidField->value,
                'message' => $invalidField->message,
            ];
        }

        return $payload;
    }
}
