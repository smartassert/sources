<?php

declare(strict_types=1);

namespace App\Tests\DataProvider;

use App\Entity\GitSource;
use App\Request\GitSourceRequest;

trait CreateUpdateGitSourceDataProviderTrait
{
    /**
     * @return array<mixed>
     */
    public function createUpdateGitSourceInvalidRequestDataProvider(): array
    {
        $label = 'label value';
        $hostUrl = 'https://example.com/repository.git';
        $path = '/';
        $labelTooLong = str_repeat('.', GitSource::LABEL_MAX_LENGTH + 1);
        $hostUrlTooLong = str_repeat('.', GitSource::HOST_URL_MAX_LENGTH + 1);
        $pathTooLong = str_repeat('a', GitSource::HOST_URL_MAX_LENGTH + 1);
        $credentialsTooLong = str_repeat('a', GitSource::CREDENTIALS_MAX_LENGTH + 1);

        return [
            'missing label' => [
                'requestParameters' => [
                    GitSourceRequest::PARAMETER_HOST_URL => $hostUrl,
                    GitSourceRequest::PARAMETER_PATH => $path,
                ],
                'expectedResponseData' => [
                    'error' => [
                        'type' => 'invalid_request',
                        'payload' => [
                            'label' => [
                                'value' => '',
                                'message' => 'This value is too short. It should have 1 character or more.',
                            ],
                        ],
                    ],
                ],
            ],
            'label too long' => [
                'requestParameters' => [
                    GitSourceRequest::PARAMETER_LABEL => $labelTooLong,
                    GitSourceRequest::PARAMETER_HOST_URL => $hostUrl,
                    GitSourceRequest::PARAMETER_PATH => $path,
                ],
                'expectedResponseData' => [
                    'error' => [
                        'type' => 'invalid_request',
                        'payload' => [
                            'label' => [
                                'value' => $labelTooLong,
                                'message' => 'This value is too long. It should have 255 characters or less.',
                            ],
                        ],
                    ],
                ],
            ],
            'missing host url' => [
                'requestParameters' => [
                    GitSourceRequest::PARAMETER_LABEL => $label,
                    GitSourceRequest::PARAMETER_PATH => $path,
                ],
                'expectedResponseData' => [
                    'error' => [
                        'type' => 'invalid_request',
                        'payload' => [
                            'host-url' => [
                                'value' => '',
                                'message' => 'This value is too short. It should have 1 character or more.',
                            ],
                        ],
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
                    'error' => [
                        'type' => 'invalid_request',
                        'payload' => [
                            'host-url' => [
                                'value' => $hostUrlTooLong,
                                'message' => 'This value is too long. It should have 255 characters or less.',
                            ],
                        ],
                    ],
                ],
            ],
            'missing path' => [
                'requestParameters' => [
                    GitSourceRequest::PARAMETER_LABEL => $label,
                    GitSourceRequest::PARAMETER_HOST_URL => $hostUrl,
                ],
                'expectedResponseData' => [
                    'error' => [
                        'type' => 'invalid_request',
                        'payload' => [
                            'path' => [
                                'value' => '',
                                'message' => 'This value is too short. It should have 1 character or more.',
                            ],
                        ],
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
                    'error' => [
                        'type' => 'invalid_request',
                        'payload' => [
                            'path' => [
                                'value' => $pathTooLong,
                                'message' => 'This value is too long. It should have 255 characters or less.',
                            ],
                        ],
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
                    'error' => [
                        'type' => 'invalid_request',
                        'payload' => [
                            'credentials' => [
                                'value' => $credentialsTooLong,
                                'message' => 'This value is too long. It should have 255 characters or less.',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
