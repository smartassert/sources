<?php

declare(strict_types=1);

namespace App\Request;

use App\Validator\YamlFileConstraint;
use SmartAssert\YamlFile\YamlFile;

class AddYamlFileRequest extends AbstractYamlFileRequest
{
    public function __construct(
        #[YamlFileConstraint]
        private YamlFile $file,
    ) {
        parent::__construct($file->name);
    }

    public function getYamlFile(): YamlFile
    {
        return $this->file;
    }
}
