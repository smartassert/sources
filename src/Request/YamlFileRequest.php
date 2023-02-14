<?php

declare(strict_types=1);

namespace App\Request;

use SmartAssert\YamlFile\Filename;

class YamlFileRequest
{
    public function __construct(
        public readonly Filename $filename,
    ) {
    }
}
