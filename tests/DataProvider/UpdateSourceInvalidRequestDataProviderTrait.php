<?php

declare(strict_types=1);

namespace App\Tests\DataProvider;

use App\Entity\GitSource;
use App\Enum\Source\Type;
use App\Request\GitSourceRequest;
use App\Request\SourceRequestInterface;

trait UpdateSourceInvalidRequestDataProviderTrait
{
    /**
     * @return array<mixed>
     */
    public function updateSourceInvalidRequestDataProvider(): array
    {
        $userId = TestConstants::AUTHENTICATED_USER_ID_PLACEHOLDER;
        $hostUrl = 'https://example.com/repository.git';
        $path = '/';
        $credentials = md5((string) rand());

        $gitSource = new GitSource($userId, $hostUrl, $path, $credentials);

        return [
            Type::GIT->value . ' missing host url' => [
                'source' => $gitSource,
                'payload' => [
                    SourceRequestInterface::PARAMETER_TYPE => Type::GIT->value,
                    GitSourceRequest::PARAMETER_HOST_URL => '',
                    GitSourceRequest::PARAMETER_PATH => $path,
                ],
                'expectedResponseData' => [
                    'error' => [
                        'type' => 'invalid_request',
                        'payload' => [
                            'host-url' => [
                                'value' => '',
                                'message' => 'This value should not be blank.',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
