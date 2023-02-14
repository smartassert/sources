<?php

declare(strict_types=1);

namespace App\Request;

use SmartAssert\YamlFile\YamlFile;

class AddYamlFileRequest extends YamlFileRequest
{
    public function __construct(private YamlFile $file)
    {
        parent::__construct($file->name);
    }

    public function getYamlFile(): YamlFile
    {
        return $this->file;
    }
}
