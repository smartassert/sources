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
        $hostUrlTooLong = str_repeat('.', GitSource::HOST_URL_MAX_LENGTH + 1);
        $pathTooLong = str_repeat('a', GitSource::HOST_URL_MAX_LENGTH + 1);

        return [
            'missing host url' => [
                'requestParameters' => [
                    GitSourceRequest::PARAMETER_PATH => '/',
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
                    GitSourceRequest::PARAMETER_HOST_URL => $hostUrlTooLong,
                    GitSourceRequest::PARAMETER_PATH => '/',
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
                    GitSourceRequest::PARAMETER_HOST_URL => 'https://example.com/repo.git',
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
                    GitSourceRequest::PARAMETER_HOST_URL => 'https://example.com/repo.git',
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
        ];
    }
}
