<?php

declare(strict_types=1);

namespace App\Entity;

trait UserSourceFileLocatorTrait
{
    public function getPath(): string
    {
        return sprintf(
            '%s/%s',
            $this->getUserId(),
            $this->getId(),
        );
    }
}
