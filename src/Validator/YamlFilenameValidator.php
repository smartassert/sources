<?php

declare(strict_types=1);

namespace App\Validator;

use App\Model\YamlFilename;

class YamlFilenameValidator
{
    public function isNameValid(string $name): bool
    {
        return '' !== $name;
    }

    public function isExtensionValid(string $extension): bool
    {
        return in_array($extension, YamlFilename::EXTENSIONS);
    }
}
