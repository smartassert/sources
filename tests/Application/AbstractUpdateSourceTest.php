<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\SourceInterface;
use App\Enum\Source\Type;
use App\Request\FileSourceRequest;
use App\Request\GitSourceRequest;
use App\Request\SourceRequestInterface;
use App\Tests\Services\SourceUserIdMutator;

abstract class AbstractUpdateSourceTest extends AbstractApplicationTest
{
    private SourceUserIdMutator $sourceUserIdMutator;

    protected function setUp(): void
    {
        parent::setUp();

        $sourceUserIdMutator = self::getContainer()->get(SourceUserIdMutator::class);
        \assert($sourceUserIdMutator instanceof SourceUserIdMutator);
        $this->sourceUserIdMutator = $sourceUserIdMutator;
    }

    /**
     * @dataProvider updateSourceInvalidRequestDataProvider
     *
     * @param array<string, string> $payload
     * @param array<mixed>          $expectedResponseData
     */
    public function testUpdateInvalidRequest(
        SourceInterface $source,
        array $payload,
        array $expectedResponseData
    ): void {
        $this->sourceUserIdMutator->setSourceUserId($source);
        $this->store->add($source);

        $response = $this->applicationClient->makeUpdateSourceRequest(
            $this->authenticationConfiguration->validToken,
            $source->getId(),
            $payload
        );

        $expectedResponseData = $this->sourceUserIdMutator->setSourceDataUserId($expectedResponseData);

        $this->responseAsserter->assertInvalidRequestJsonResponse($response, $expectedResponseData);
    }

    /**
     * @return array<mixed>
     */
    public function updateSourceInvalidRequestDataProvider(): array
    {
        $userId = SourceUserIdMutator::AUTHENTICATED_USER_ID_PLACEHOLDER;
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

    /**
     * @dataProvider updateSourceSuccessDataProvider
     *
     * @param array<string, string> $payload
     * @param array<mixed>          $expectedResponseData
     */
    public function testUpdateSuccess(
        SourceInterface $source,
        array $payload,
        array $expectedResponseData
    ): void {
        $this->sourceUserIdMutator->setSourceUserId($source);
        $this->store->add($source);

        $response = $this->applicationClient->makeUpdateSourceRequest(
            $this->authenticationConfiguration->validToken,
            $source->getId(),
            $payload
        );

        $expectedResponseData = $this->sourceUserIdMutator->setSourceDataUserId($expectedResponseData);

        $this->responseAsserter->assertSuccessfulJsonResponse($response, $expectedResponseData);
    }

    /**
     * @return array<mixed>
     */
    public function updateSourceSuccessDataProvider(): array
    {
        $userId = SourceUserIdMutator::AUTHENTICATED_USER_ID_PLACEHOLDER;
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
