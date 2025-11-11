<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Tests\Application\AbstractFileSourceFileTest;

class FileSourceFileTest extends AbstractFileSourceFileTest
{
    use GetClientAdapterTrait;

    public static function storeFileInvalidRequestDataProvider(): array
    {
        return array_merge(
            self::storeFileInvalidRequestDefaultDataProvider(),
            self::storeFileInvalidRequestBackslashInFilenameDataProvider(),
        );
    }

    /**
     * @return array<mixed>
     */
    public static function storeFileInvalidRequestBackslashInFilenameDataProvider(): array
    {
        return [
            'name contains backslash characters, content non-empty' => [
                'filename' => 'one-two-\-three.yaml',
                'content' => 'non-empty value',
                'expectedResponseData' => [
                    'class' => 'bad_request',
                    'parameter' => [
                        'name' => 'filename',
                        'value' => 'one-two-\-three.yaml',
                        'requirements' => [
                            'data_type' => 'yaml_filename',
                        ],
                    ],
                    'type' => 'invalid',
                ],
            ],
        ];
    }

    public static function yamlFileInvalidRequestDataProvider(): array
    {
        return array_merge(
            self::yamlFileInvalidRequestDefaultDataProvider(),
            self::yamlFileInvalidRequestBackslashInFilenameDataProvider(),
        );
    }

    /**
     * @return array<mixed>
     */
    public static function yamlFileInvalidRequestBackslashInFilenameDataProvider(): array
    {
        return [
            'name contains backslash characters' => [
                'filename' => 'one-two-\-three.yaml',
            ],
        ];
    }
}
