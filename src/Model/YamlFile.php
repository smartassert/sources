<?php

declare(strict_types=1);

namespace App\Model;

class YamlFile
{
    public function __construct(
        public readonly YamlFilename $name,
        public readonly string $content,
    ) {
    }
}
