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
                    'class' => 'bad_request',
                    'parameter' => [
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
                    'type' => 'wrong_size',
                ],
            ],
            'label length exceeds length limit' => [
                'requestParameters' => [
                    GitSourceRequest::PARAMETER_LABEL => $labelTooLong,
                ],
                'expectedResponseData' => [
                    'class' => 'bad_request',
                    'parameter' => [
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
                    'type' => 'wrong_size',
                ],
            ],
            'missing host url' => [
                'requestParameters' => [
                    GitSourceRequest::PARAMETER_LABEL => $label,
                    GitSourceRequest::PARAMETER_PATH => $path,
                ],
                'expectedResponseData' => [
                    'class' => 'bad_request',
                    'parameter' => [
                        'name' => 'host-url',
                        'value' => '',
                        'requirements' => [
                            'data_type' => 'string',
                            'size' => [
                                'minimum' => 1,
                                'maximum' => 255,
                            ]
                        ],
                    ],
                    'type' => 'wrong_size',
                ],
            ],
            'host url too long' => [
                'requestParameters' => [
                    GitSourceRequest::PARAMETER_LABEL => $label,
                    GitSourceRequest::PARAMETER_HOST_URL => $hostUrlTooLong,
                    GitSourceRequest::PARAMETER_PATH => $path,
                ],
                'expectedResponseData' => [
                    'class' => 'bad_request',
                    'parameter' => [
                        'name' => 'host-url',
                        'value' => $hostUrlTooLong,
                        'requirements' => [
                            'data_type' => 'string',
                            'size' => [
                                'minimum' => 1,
                                'maximum' => 255,
                            ]
                        ],
                    ],
                    'type' => 'wrong_size',
                ],
            ],
            'missing path' => [
                'requestParameters' => [
                    GitSourceRequest::PARAMETER_LABEL => $label,
                    GitSourceRequest::PARAMETER_HOST_URL => $hostUrl,
                ],
                'expectedResponseData' => [
                    'class' => 'bad_request',
                    'parameter' => [
                        'name' => 'path',
                        'value' => '',
                        'requirements' => [
                            'data_type' => 'string',
                            'size' => [
                                'minimum' => 1,
                                'maximum' => 255,
                            ]
                        ],
                    ],
                    'type' => 'wrong_size',
                ],
            ],
            'path too long' => [
                'requestParameters' => [
                    GitSourceRequest::PARAMETER_LABEL => $label,
                    GitSourceRequest::PARAMETER_HOST_URL => $hostUrl,
                    GitSourceRequest::PARAMETER_PATH => $pathTooLong,
                ],
                'expectedResponseData' => [
                    'class' => 'bad_request',
                    'parameter' => [
                        'name' => 'path',
                        'value' => $pathTooLong,
                        'requirements' => [
                            'data_type' => 'string',
                            'size' => [
                                'minimum' => 1,
                                'maximum' => 255,
                            ]
                        ],
                    ],
                    'type' => 'wrong_size',
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
                    'class' => 'bad_request',
                    'parameter' => [
                        'name' => 'credentials',
                        'value' => $credentialsTooLong,
                        'requirements' => [
                            'data_type' => 'string',
                            'size' => [
                                'minimum' => 0,
                                'maximum' => 255,
                            ]
                        ],
                    ],
                    'type' => 'wrong_size',
                ],
            ],
        ];
    }
}
