<?php

declare(strict_types=1);

namespace App\Message;

class Prepare
{
    public function __construct(
        private string $sourceId
    ) {
    }

    public function getSourceId(): string
    {
        return $this->sourceId;
    }
}
