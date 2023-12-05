<?php

declare(strict_types=1);

namespace App\ErrorResponse;

interface SerializableErrorInterface extends \JsonSerializable, HasHttpStatusCodeInterface
{
    public function getStatusCode(): int;

    /**
     * @return array<mixed>
     */
    public function jsonSerialize(): array;
}
