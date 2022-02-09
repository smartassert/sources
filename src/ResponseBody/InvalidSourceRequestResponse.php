<?php

declare(strict_types=1);

namespace App\ResponseBody;

class InvalidSourceRequestResponse implements ErrorInterface
{
    public const TYPE = 'invalid_source_request';

    /**
     * @param string[] $missingRequiredFields
     */
    public function __construct(
        private string $type,
        private array $missingRequiredFields,
    ) {
    }

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getPayload(): array
    {
        return [
            'source_type' => $this->type,
            'missing_required_fields' => $this->missingRequiredFields,
        ];
    }
}
