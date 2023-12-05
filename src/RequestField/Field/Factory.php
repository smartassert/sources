<?php

declare(strict_types=1);

namespace App\RequestField\Field;

use App\RequestField\FieldInterface;

readonly class Factory
{
    /**
     * @param non-empty-string $name
     */
    public function createStringField(
        string $name,
        string $value,
        int $minimumLength,
        int $maximumLength
    ): FieldInterface {
        return new Field($name, $value, new Requirements('string', new Size($minimumLength, $maximumLength)));
    }

    /**
     * @param non-empty-string $name
     */
    public function createYamlFilenameField(string $name, string $value): FieldInterface
    {
        return new Field($name, $value, new Requirements('yaml_filename'));
    }
}
