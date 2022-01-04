<?php

declare(strict_types=1);

namespace App\Model;

trait StringableFileLocatorTrait
{
    public function __toString(): string
    {
        return $this->getPath();
    }
}
