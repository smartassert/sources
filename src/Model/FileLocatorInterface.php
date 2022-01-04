<?php

declare(strict_types=1);

namespace App\Model;

interface FileLocatorInterface
{
    public function getPath(): string;
}
