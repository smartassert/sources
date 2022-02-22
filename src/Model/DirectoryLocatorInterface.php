<?php

declare(strict_types=1);

namespace App\Model;

interface DirectoryLocatorInterface
{
    public function getDirectoryPath(): string;
}
