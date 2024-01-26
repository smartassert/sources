<?php

declare(strict_types=1);

namespace App\RequestParameter;

use SmartAssert\ServiceRequest\Parameter\Parameter;
use SmartAssert\ServiceRequest\Parameter\ParameterInterface;
use SmartAssert\ServiceRequest\Parameter\Requirements;
use SmartAssert\ServiceRequest\Parameter\Size;

readonly class Factory
{
    /**
     * @param non-empty-string $name
     */
    public function createStringParameter(
        string $name,
        string $value,
        int $minimumLength,
        int $maximumLength
    ): ParameterInterface {
        return (new Parameter($name, $value))
            ->withRequirements(new Requirements('string', new Size($minimumLength, $maximumLength)))
        ;
    }

    /**
     * @param non-empty-string $name
     */
    public function createYamlFilenameParameter(string $name, string $value): ParameterInterface
    {
        return (new Parameter($name, $value))
            ->withRequirements(new Requirements('yaml_filename'))
        ;
    }

    /**
     * @param non-empty-string $name
     * @param string[]         $value
     */
    public function createYamlFilenameCollectionParameter(string $name, array $value): ParameterInterface
    {
        return (new Parameter($name, $value))
            ->withRequirements(new Requirements('yaml_filename_collection'))
        ;
    }
}
