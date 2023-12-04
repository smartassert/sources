<?php

declare(strict_types=1);

namespace App\ErrorResponse;

use App\Entity\IdentifyingEntityInterface;

interface EntityErrorInterface
{
    public function getEntity(): IdentifyingEntityInterface;
}
