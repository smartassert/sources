<?php

declare(strict_types=1);

namespace App\RequestField\Field;

use SmartAssert\ServiceRequest\Field\Field;
use SmartAssert\ServiceRequest\Field\FieldInterface;
use SmartAssert\ServiceRequest\Field\Requirements;
use SmartAssert\ServiceRequest\Field\Size;

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
        return (new Field($name, $value))
            ->withRequirements(new Requirements('string', new Size($minimumLength, $maximumLength)))
        ;
    }

    /**
     * @param non-empty-string $name
     */
    public function createYamlFilenameField(string $name, string $value): FieldInterface
    {
        return (new Field($name, $value))
            ->withRequirements(new Requirements('yaml_filename'))
        ;
    }

    /**
     * @param non-empty-string $name
     * @param string[]         $value
     */
    public function createYamlFilenameCollectionField(string $name, array $value): FieldInterface
    {
        return (new Field($name, $value))
            ->withRequirements(new Requirements('yaml_filename_collection'))
        ;
    }
}
