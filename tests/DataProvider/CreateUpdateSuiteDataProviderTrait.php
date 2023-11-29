<?php

declare(strict_types=1);

namespace App\Tests\DataProvider;

use App\Entity\AbstractSource;
use App\Request\SuiteRequest;

trait CreateUpdateSuiteDataProviderTrait
{
    /**
     * @return array<mixed>
     */
    public static function createUpdateSuiteInvalidRequestDataProvider(): array
    {
        $labelTooLong = str_repeat('.', AbstractSource::LABEL_MAX_LENGTH + 1);

        return [
            'missing label' => [
                'requestParameters' => [],
                'expectedResponseData' => [
                    'class' => 'invalid_request_field',
                    'field' => [
                        'name' => 'label',
                        'value' => '',
                    ],
                    'type' => 'empty',
                    'requirements' => [
                        'data_type' => 'string',
                        'size' => [
                            'minimum' => 1,
                            'maximum' => 255,
                        ]
                    ],
                ],
            ],
            'label length exceeds length limit' => [
                'requestParameters' => [
                    SuiteRequest::PARAMETER_LABEL => $labelTooLong,
                ],
                'expectedResponseData' => [
                    'class' => 'invalid_request_field',
                    'field' => [
                        'name' => 'label',
                        'value' => $labelTooLong,
                    ],
                    'type' => 'too_large',
                    'requirements' => [
                        'data_type' => 'string',
                        'size' => [
                            'minimum' => 1,
                            'maximum' => 255,
                        ]
                    ],
                ],
            ],
            'invalid yaml filename within singular tests collection' => [
                'requestParameters' => [
                    SuiteRequest::PARAMETER_LABEL => md5((string) rand()),
                    SuiteRequest::PARAMETER_TESTS => ['test.txt'],
                ],
                'expectedResponseData' => [
                    'class' => 'invalid_request_field',
                    'field' => [
                        'name' => 'tests',
                        'position' => 1,
                        'value' => [
                            'test.txt',
                        ],
                    ],
                    'type' => 'invalid',
                    'requirements' => [
                        'data_type' => 'yaml_filename_collection'
                    ],
                ],
            ],
            'invalid yaml filename within tests collection' => [
                'requestParameters' => [
                    SuiteRequest::PARAMETER_LABEL => md5((string) rand()),
                    SuiteRequest::PARAMETER_TESTS => ['test.yaml', 'test.txt', 'test.yml'],
                ],
                'expectedResponseData' => [
                    'class' => 'invalid_request_field',
                    'field' => [
                        'name' => 'tests',
                        'position' => 2,
                        'value' => [
                            'test.yaml',
                            'test.txt',
                            'test.yml',
                        ],
                    ],
                    'type' => 'invalid',
                    'requirements' => [
                        'data_type' => 'yaml_filename_collection'
                    ],
                ],
            ],
        ];
    }
}
