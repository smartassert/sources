<?php

declare(strict_types=1);

namespace App\ErrorResponse;

use App\Entity\IdentifyingEntityInterface;

interface EntityErrorInterface extends ErrorInterface
{
    public function getEntity(): IdentifyingEntityInterface;
}
