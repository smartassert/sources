<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Entity\SourceInterface;
use App\Enum\Source\Type;
use App\Repository\SourceRepository;
use App\Request\FileSourceRequest;
use App\Request\GitSourceRequest;
use App\Request\InvalidSourceTypeRequest;
use App\Request\SourceRequestInterface;
use App\Services\Source\Store;
use App\Tests\Model\UserId;
use App\Tests\Services\AuthorizationRequestAsserter;
use App\Tests\Services\EntityRemover;
use Symfony\Component\HttpFoundation\JsonResponse;

class SourceControllerTest extends AbstractSourceControllerTest
{
    private SourceRepository $sourceRepository;
    private Store $store;
    private AuthorizationRequestAsserter $authorizationRequestAsserter;

    protected function setUp(): void
    {
        parent::setUp();

        $store = self::getContainer()->get(Store::class);
        \assert($store instanceof Store);
        $this->store = $store;

        $sourceRepository = self::getContainer()->get(SourceRepository::class);
        \assert($sourceRepository instanceof SourceRepository);
        $this->sourceRepository = $sourceRepository;

        $authorizationRequestAsserter = self::getContainer()->get(AuthorizationRequestAsserter::class);
        \assert($authorizationRequestAsserter instanceof AuthorizationRequestAsserter);
        $this->authorizationRequestAsserter = $authorizationRequestAsserter;

        $entityRemover = self::getContainer()->get(EntityRemover::class);
        if ($entityRemover instanceof EntityRemover) {
            $entityRemover->removeAll();
        }
    }

    /**
     * @dataProvider createInvalidRequestDataProvider
     *
     * @param array<string, string> $requestParameters
     * @param array<string, string> $expectedResponseData
     */
    public function testCreateInvalidSourceRequest(array $requestParameters, array $expectedResponseData): void
    {
        $userId = UserId::create();
        $this->setUserServiceAuthorizedResponse($userId);

        $response = $this->applicationClient->makeAuthorizedRequest(
            'POST',
            $this->generateUrl('source_create'),
            $requestParameters
        );

        self::assertSame(400, $response->getStatusCode());
        self::assertInstanceOf(JsonResponse::class, $response);

        self::assertSame(
            $expectedResponseData,
            json_decode((string) $response->getContent(), true)
        );
    }

    /**
     * @return array<mixed>
     */
    public function createInvalidRequestDataProvider(): array
    {
        return [
            'invalid source type' => [
                'requestParameters' => [
                    SourceRequestInterface::PARAMETER_TYPE => 'invalid',
                ],
                'expectedResponseData' => [
                    'error' => [
                        'type' => 'invalid_request',
                        'payload' => [
                            'type' => [
                                'value' => 'invalid',
                                'message' => InvalidSourceTypeRequest::ERROR_MESSAGE,
                            ],
                        ],
                    ],
                ],
            ],
            'git source missing host url' => [
                'requestParameters' => [
                    SourceRequestInterface::PARAMETER_TYPE => Type::GIT->value,
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
     * @dataProvider createSuccessDataProvider
     *
     * @param array<string, string> $requestParameters
     * @param array<mixed>          $expected
     */
    public function testCreateSuccess(string $userId, array $requestParameters, array $expected): void
    {
        $this->setUserServiceAuthorizedResponse($userId);

        $response = $this->applicationClient->makeAuthorizedRequest(
            'POST',
            $this->generateUrl('source_create'),
            $requestParameters
        );

        self::assertSame(200, $response->getStatusCode());
        $this->authorizationRequestAsserter->assertAuthorizationRequestIsMade();

        $sources = $this->sourceRepository->findAll();
        self::assertIsArray($sources);
        self::assertCount(1, $sources);

        $source = $sources[0];
        self::assertInstanceOf(SourceInterface::class, $source);

        $expected['id'] = $source->getId();
        self::assertEquals($expected, json_decode((string) $response->getContent(), true));
    }

    /**
     * @return array<mixed>
     */
    public function createSuccessDataProvider(): array
    {
        $userId = UserId::create();
        $hostUrl = 'https://example.com/repository.git';
        $path = '/';
        $credentials = md5((string) rand());
        $label = 'file source label';

        return [
            'git source, credentials missing' => [
                'userId' => $userId,
                'requestParameters' => [
                    SourceRequestInterface::PARAMETER_TYPE => Type::GIT->value,
                    GitSourceRequest::PARAMETER_HOST_URL => $hostUrl,
                    GitSourceRequest::PARAMETER_PATH => $path
                ],
                'expected' => [
                    'user_id' => $userId,
                    'type' => Type::GIT->value,
                    'host_url' => $hostUrl,
                    'path' => $path,
                    'has_credentials' => false,
                ],
            ],
            'git source, credentials present' => [
                'userId' => $userId,
                'requestParameters' => [
                    SourceRequestInterface::PARAMETER_TYPE => Type::GIT->value,
                    GitSourceRequest::PARAMETER_HOST_URL => $hostUrl,
                    GitSourceRequest::PARAMETER_PATH => $path,
                    GitSourceRequest::PARAMETER_CREDENTIALS => $credentials,
                ],
                'expected' => [
                    'user_id' => $userId,
                    'type' => Type::GIT->value,
                    'host_url' => $hostUrl,
                    'path' => $path,
                    'has_credentials' => true,
                ],
            ],
            'file source' => [
                'userId' => $userId,
                'requestParameters' => [
                    SourceRequestInterface::PARAMETER_TYPE => Type::FILE->value,
                    FileSourceRequest::PARAMETER_LABEL => $label
                ],
                'expected' => [
                    'user_id' => $userId,
                    'type' => Type::FILE->value,
                    'label' => $label,
                ],
            ],
        ];
    }

    /**
     * @dataProvider listSuccessDataProvider
     *
     * @param SourceInterface[] $sources
     * @param array<mixed>      $expectedResponseData
     */
    public function testListSuccess(array $sources, string $userId, array $expectedResponseData): void
    {
        foreach ($sources as $source) {
            $this->store->add($source);
        }

        $this->setUserServiceAuthorizedResponse($userId);

        $response = $this->applicationClient->makeAuthorizedRequest(
            'GET',
            $this->generateUrl('source_list')
        );

        self::assertSame(200, $response->getStatusCode());
        $this->authorizationRequestAsserter->assertAuthorizationRequestIsMade();
        self::assertInstanceOf(JsonResponse::class, $response);

        $responseData = json_decode((string) $response->getContent(), true);
        self::assertEquals($expectedResponseData, $responseData);
    }

    /**
     * @return array<mixed>
     */
    public function listSuccessDataProvider(): array
    {
        $userId = UserId::create();
        $userFileSources = [
            new FileSource($userId, 'file source label'),
        ];

        $userGitSources = [
            new GitSource($userId, 'https://example.com/repository.git'),
        ];

        $userRunSources = [
            new RunSource($userFileSources[0]),
            new RunSource($userGitSources[0]),
        ];

        return [
            'no sources' => [
                'sources' => [],
                'userId' => $userId,
                'expectedResponseData' => [],
            ],
            'has file, git and run sources, no user match' => [
                'sources' => [
                    new FileSource(UserId::create(), 'file source label'),
                    new GitSource(UserId::create(), 'https://example.com/repository.git'),
                    new RunSource(
                        new FileSource(UserId::create(), 'file source label'),
                    ),
                ],
                'userId' => $userId,
                'expectedResponseData' => [],
            ],
            'has file and git sources for correct user only' => [
                'sources' => [
                    $userFileSources[0],
                    $userGitSources[0],
                ],
                'userId' => $userId,
                'expectedResponseData' => [
                    $userFileSources[0]->jsonSerialize(),
                    $userGitSources[0]->jsonSerialize(),
                ],
            ],
            'has file, git and run sources for correct user only' => [
                'sources' => [
                    $userFileSources[0],
                    $userGitSources[0],
                    $userRunSources[0],
                    $userRunSources[1],
                ],
                'userId' => $userId,
                'expectedResponseData' => [
                    $userFileSources[0]->jsonSerialize(),
                    $userGitSources[0]->jsonSerialize(),
                ],
            ],
            'has file, git and run sources for mixed users' => [
                'sources' => [
                    $userFileSources[0],
                    new FileSource(UserId::create(), 'file source label'),
                    $userGitSources[0],
                    new GitSource(UserId::create(), 'https://example.com/repository.git'),
                    $userRunSources[0],
                    $userRunSources[1],
                    new RunSource(
                        new FileSource(UserId::create(), 'file source label')
                    ),
                    new RunSource(
                        new GitSource(UserId::create(), 'https://example.com/repository.git')
                    )
                ],
                'userId' => $userId,
                'expectedResponseData' => [
                    $userFileSources[0]->jsonSerialize(),
                    $userGitSources[0]->jsonSerialize(),
                ],
            ],
        ];
    }
}
