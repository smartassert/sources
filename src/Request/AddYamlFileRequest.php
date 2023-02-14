<?php

declare(strict_types=1);

namespace App\Request;

use SmartAssert\YamlFile\YamlFile;

class AddYamlFileRequest extends YamlFileRequest
{
    public function __construct(
        public readonly YamlFile $file,
    ) {
        parent::__construct($file->name);
    }
}
