<?php

declare(strict_types=1);

namespace App\Request;

use App\Validator\YamlFilenameConstraint;
use SmartAssert\YamlFile\Filename;

class YamlFileRequest
{
    public function __construct(
        #[YamlFilenameConstraint]
        private Filename $filename,
    ) {
    }

    public function getFilename(): Filename
    {
        return $this->filename;
    }
}
