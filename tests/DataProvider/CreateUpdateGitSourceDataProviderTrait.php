<?php

declare(strict_types=1);

namespace App\Tests\DataProvider;

use App\Entity\AbstractOriginSource;
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
        $labelTooLong = str_repeat('.', AbstractOriginSource::LABEL_MAX_LENGTH + 1);
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
                            'name' => 'label',
                            'value' => '',
                            'message' => 'This value should be between 1 and 255 characters long.',
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
                            'name' => 'label',
                            'value' => $labelTooLong,
                            'message' => 'This value should be between 1 and 255 characters long.',
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
                            'name' => 'host-url',
                            'value' => '',
                            'message' => 'This value is too short. It should have 1 character or more.',
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
                            'name' => 'host-url',
                            'value' => $hostUrlTooLong,
                            'message' => 'This value is too long. It should have 255 characters or less.',
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
                            'name' => 'path',
                            'value' => '',
                            'message' => 'This value is too short. It should have 1 character or more.',
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
                            'name' => 'path',
                            'value' => $pathTooLong,
                            'message' => 'This value is too long. It should have 255 characters or less.',
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
                            'name' => 'credentials',
                            'value' => $credentialsTooLong,
                            'message' => 'This value is too long. It should have 255 characters or less.',
                        ],
                    ],
                ],
            ],
        ];
    }
}
