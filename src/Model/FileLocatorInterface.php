<?php

declare(strict_types=1);

namespace App\Model;

interface FileLocatorInterface extends \Stringable
{
    public function getPath(): string;
}
