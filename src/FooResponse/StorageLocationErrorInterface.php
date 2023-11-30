<?php

declare(strict_types=1);

namespace App\FooResponse;

interface StorageLocationErrorInterface
{
    public function getLocation(): ?string;
}
