<?php

declare(strict_types=1);

namespace App\Entity;

use App\Model\StringableFileLocatorTrait;

trait UserSourceFileLocatorTrait
{
    use StringableFileLocatorTrait;

    public function getPath(): string
    {
        return sprintf(
            '%s/%s',
            $this->getUserId(),
            $this->getId(),
        );
    }
}
