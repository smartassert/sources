<?php

declare(strict_types=1);

namespace App\RequestParameter;

use SmartAssert\ServiceRequest\Parameter\Factory as BaseFactory;
use SmartAssert\ServiceRequest\Parameter\Parameter;
use SmartAssert\ServiceRequest\Parameter\ParameterInterface;
use SmartAssert\ServiceRequest\Parameter\Requirements;

readonly class Factory extends BaseFactory
{
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
