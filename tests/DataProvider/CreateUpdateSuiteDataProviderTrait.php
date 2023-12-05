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
                    'class' => 'bad_request',
                    'field' => [
                        'name' => 'label',
                        'value' => '',
                        'requirements' => [
                            'data_type' => 'string',
                            'size' => [
                                'minimum' => 1,
                                'maximum' => 255,
                            ]
                        ],
                    ],
                    'type' => 'empty',
                ],
            ],
            'label length exceeds length limit' => [
                'requestParameters' => [
                    SuiteRequest::PARAMETER_LABEL => $labelTooLong,
                ],
                'expectedResponseData' => [
                    'class' => 'bad_request',
                    'field' => [
                        'name' => 'label',
                        'value' => $labelTooLong,
                        'requirements' => [
                            'data_type' => 'string',
                            'size' => [
                                'minimum' => 1,
                                'maximum' => 255,
                            ]
                        ],
                    ],
                    'type' => 'too_large',
                ],
            ],
            'invalid yaml filename within singular tests collection' => [
                'requestParameters' => [
                    SuiteRequest::PARAMETER_LABEL => md5((string) rand()),
                    SuiteRequest::PARAMETER_TESTS => ['test.txt'],
                ],
                'expectedResponseData' => [
                    'class' => 'bad_request',
                    'field' => [
                        'name' => 'tests',
                        'position' => 1,
                        'value' => [
                            'test.txt',
                        ],
                        'requirements' => [
                            'data_type' => 'yaml_filename_collection'
                        ],
                    ],
                    'type' => 'invalid',
                ],
            ],
            'invalid yaml filename within tests collection' => [
                'requestParameters' => [
                    SuiteRequest::PARAMETER_LABEL => md5((string) rand()),
                    SuiteRequest::PARAMETER_TESTS => ['test.yaml', 'test.txt', 'test.yml'],
                ],
                'expectedResponseData' => [
                    'class' => 'bad_request',
                    'field' => [
                        'name' => 'tests',
                        'position' => 2,
                        'value' => [
                            'test.yaml',
                            'test.txt',
                            'test.yml',
                        ],
                        'requirements' => [
                            'data_type' => 'yaml_filename_collection'
                        ],
                    ],
                    'type' => 'invalid',
                ],
            ],
        ];
    }
}
