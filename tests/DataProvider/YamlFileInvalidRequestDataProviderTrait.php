<?php

declare(strict_types=1);

namespace App\Tests\DataProvider;

use App\Tests\Services\InvalidFilenameResponseDataFactory;
use App\Validator\YamlFilenameConstraint;

trait YamlFileInvalidRequestDataProviderTrait
{
    /**
     * @return array<mixed>
     */
    public function yamlFileInvalidRequestDataProvider(): array
    {
        return [
            'name empty with .yaml extension' => [
                'filename' => '.yaml',
                'expectedResponseData' => InvalidFilenameResponseDataFactory::createForMessage(
                    YamlFilenameConstraint::MESSAGE_NAME_EMPTY
                ),
            ],
            'name contains backslash characters' => [
                'filename' => 'one-two-\\-three.yaml',
                'expectedResponseData' => InvalidFilenameResponseDataFactory::createForMessage(
                    YamlFilenameConstraint::MESSAGE_FILENAME_INVALID
                ),
            ],
            'name contains space characters' => [
                'filename' => 'one two three.yaml',
                'expectedResponseData' => InvalidFilenameResponseDataFactory::createForMessage(
                    YamlFilenameConstraint::MESSAGE_FILENAME_INVALID
                ),
            ],
        ];
    }
}
