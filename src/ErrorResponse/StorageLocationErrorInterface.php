<?php

declare(strict_types=1);

namespace App\ErrorResponse;

interface StorageLocationErrorInterface
{
    public function getLocation(): ?string;
}
