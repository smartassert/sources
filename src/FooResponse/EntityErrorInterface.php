<?php

declare(strict_types=1);

namespace App\FooResponse;

use App\Entity\IdentifyingEntityInterface;

interface EntityErrorInterface
{
    public function getEntity(): IdentifyingEntityInterface;
}
