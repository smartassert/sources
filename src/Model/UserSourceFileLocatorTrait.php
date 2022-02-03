<?php

declare(strict_types=1);

namespace App\Model;

trait UserSourceFileLocatorTrait
{
    public function __toString(): string
    {
        return $this->getPath();
    }

    public function getPath(): string
    {
        return sprintf(
            '%s/%s',
            $this->getUserId(),
            $this->getId(),
        );
    }
}
