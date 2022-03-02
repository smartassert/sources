<?php

declare(strict_types=1);

namespace App\Tests\DataProvider;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Enum\Source\Type;
use App\Request\FileSourceRequest;
use App\Request\GitSourceRequest;
use App\Request\SourceRequestInterface;

trait UpdateSourceSuccessDataProviderTrait
{
    /**
     * @return array<mixed>
     */
    public function updateSourceSuccessDataProvider(): array
    {
        $userId = TestConstants::AUTHENTICATED_USER_ID_PLACEHOLDER;
        $hostUrl = 'https://example.com/repository.git';
        $path = '/';
        $credentials = md5((string) rand());
        $newHostUrl = 'https://new.example.com/repository.git';
        $newPath = '/new';

        $label = 'file source label';
        $newLabel = 'new file source label';

        $fileSource = new FileSource($userId, $label);
        $gitSource = new GitSource($userId, $hostUrl, $path, $credentials);

        return [
            Type::FILE->value => [
                'source' => $fileSource,
                'payload' => [
                    SourceRequestInterface::PARAMETER_TYPE => Type::FILE->value,
                    FileSourceRequest::PARAMETER_LABEL => $newLabel,
                ],
                'expectedResponseData' => [
                    'id' => $fileSource->getId(),
                    'user_id' => $fileSource->getUserId(),
                    'type' => Type::FILE->value,
                    'label' => $newLabel,
                ],
            ],
            Type::GIT->value . ' credentials present and empty' => [
                'source' => $gitSource,
                'payload' => [
                    SourceRequestInterface::PARAMETER_TYPE => Type::GIT->value,
                    GitSourceRequest::PARAMETER_HOST_URL => $newHostUrl,
                    GitSourceRequest::PARAMETER_PATH => $newPath,
                    GitSourceRequest::PARAMETER_CREDENTIALS => null,
                ],
                'expectedResponseData' => [
                    'id' => $gitSource->getId(),
                    'user_id' => $gitSource->getUserId(),
                    'type' => Type::GIT->value,
                    'host_url' => $newHostUrl,
                    'path' => $newPath,
                    'has_credentials' => false,
                ],
            ],
            Type::GIT->value . ' credentials not present' => [
                'source' => $gitSource,
                'payload' => [
                    SourceRequestInterface::PARAMETER_TYPE => Type::GIT->value,
                    GitSourceRequest::PARAMETER_HOST_URL => $newHostUrl,
                    GitSourceRequest::PARAMETER_PATH => $newPath,
                ],
                'expectedResponseData' => [
                    'id' => $gitSource->getId(),
                    'user_id' => $gitSource->getUserId(),
                    'type' => Type::GIT->value,
                    'host_url' => $newHostUrl,
                    'path' => $newPath,
                    'has_credentials' => false,
                ],
            ],
        ];
    }
}
