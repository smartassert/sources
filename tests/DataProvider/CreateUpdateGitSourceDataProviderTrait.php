<?php

declare(strict_types=1);

namespace App\Tests\DataProvider;

use App\Entity\AbstractSource;
use App\Entity\GitSource;
use App\Request\GitSourceRequest;

trait CreateUpdateGitSourceDataProviderTrait
{
    /**
     * @return array<mixed>
     */
    public static function createUpdateGitSourceInvalidRequestDataProvider(): array
    {
        $label = 'label value';
        $hostUrl = 'https://example.com/repository.git';
        $path = '/';
        $labelTooLong = str_repeat('.', AbstractSource::LABEL_MAX_LENGTH + 1);
        $hostUrlTooLong = str_repeat('.', GitSource::HOST_URL_MAX_LENGTH + 1);
        $pathTooLong = str_repeat('a', GitSource::HOST_URL_MAX_LENGTH + 1);
        $credentialsTooLong = str_repeat('a', GitSource::CREDENTIALS_MAX_LENGTH + 1);

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
                    GitSourceRequest::PARAMETER_LABEL => $labelTooLong,
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
            'missing host url' => [
                'requestParameters' => [
                    GitSourceRequest::PARAMETER_LABEL => $label,
                    GitSourceRequest::PARAMETER_PATH => $path,
                ],
                'expectedResponseData' => [
                    'class' => 'invalid_request_field',
                    'field' => [
                        'name' => 'host-url',
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
            'host url too long' => [
                'requestParameters' => [
                    GitSourceRequest::PARAMETER_LABEL => $label,
                    GitSourceRequest::PARAMETER_HOST_URL => $hostUrlTooLong,
                    GitSourceRequest::PARAMETER_PATH => $path,
                ],
                'expectedResponseData' => [
                    'class' => 'invalid_request_field',
                    'field' => [
                        'name' => 'host-url',
                        'value' => $hostUrlTooLong,
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
            'missing path' => [
                'requestParameters' => [
                    GitSourceRequest::PARAMETER_LABEL => $label,
                    GitSourceRequest::PARAMETER_HOST_URL => $hostUrl,
                ],
                'expectedResponseData' => [
                    'class' => 'invalid_request_field',
                    'field' => [
                        'name' => 'path',
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
            'path too long' => [
                'requestParameters' => [
                    GitSourceRequest::PARAMETER_LABEL => $label,
                    GitSourceRequest::PARAMETER_HOST_URL => $hostUrl,
                    GitSourceRequest::PARAMETER_PATH => $pathTooLong,
                ],
                'expectedResponseData' => [
                    'class' => 'invalid_request_field',
                    'field' => [
                        'name' => 'path',
                        'value' => $pathTooLong,
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
            'credentials too long' => [
                'requestParameters' => [
                    GitSourceRequest::PARAMETER_LABEL => $label,
                    GitSourceRequest::PARAMETER_HOST_URL => $hostUrl,
                    GitSourceRequest::PARAMETER_PATH => $path,
                    GitSourceRequest::PARAMETER_CREDENTIALS => $credentialsTooLong,
                ],
                'expectedResponseData' => [
                    'class' => 'invalid_request_field',
                    'field' => [
                        'name' => 'credentials',
                        'value' => $credentialsTooLong,
                    ],
                    'type' => 'too_large',
                    'requirements' => [
                        'data_type' => 'string',
                        'size' => [
                            'minimum' => 0,
                            'maximum' => 255,
                        ]
                    ],
                ],
            ],
        ];
    }
}
