<?php

declare(strict_types=1);

namespace App\Model;

class YamlFile
{
    public const EXTENSIONS = ['yaml'];

    public function __construct(
        public readonly Filename $name,
        public readonly string $content,
    ) {
    }
}
