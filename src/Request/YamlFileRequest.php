<?php

declare(strict_types=1);

namespace App\Request;

use SmartAssert\YamlFile\Filename;

class YamlFileRequest
{
    public function __construct(private Filename $filename)
    {
    }

    public function getFilename(): Filename
    {
        return $this->filename;
    }
}
