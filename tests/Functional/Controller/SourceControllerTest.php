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
use App\Tests\Services\EntityRemover;

class SourceControllerTest extends AbstractSourceControllerTest
{
    private SourceRepository $sourceRepository;
    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $store = self::getContainer()->get(Store::class);
        \assert($store instanceof Store);
        $this->store = $store;

        $sourceRepository = self::getContainer()->get(SourceRepository::class);
        \assert($sourceRepository instanceof SourceRepository);
        $this->sourceRepository = $sourceRepository;

        $entityRemover = self::getContainer()->get(EntityRemover::class);
        if ($entityRemover instanceof EntityRemover) {
            $entityRemover->removeAll();
        }
    }

    public function testCreateUnauthorizedUser(): void
    {
        $response = $this->application->makeCreateSourceRequest($this->invalidToken, []);

        $this->responseAsserter->assertUnauthorizedResponse($response);
        $this->requestAsserter->assertAuthorizationRequestIsMade($this->invalidToken);
    }

    /**
     * @dataProvider createInvalidRequestDataProvider
     *
     * @param array<string, string> $requestParameters
     * @param array<string, string> $expectedResponseData
     */
    public function testCreateInvalidSourceRequest(array $requestParameters, array $expectedResponseData): void
    {
        $response = $this->application->makeCreateSourceRequest($this->validToken, $requestParameters);

        $this->responseAsserter->assertInvalidRequestJsonResponse($response, $expectedResponseData);
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
    public function testCreateSuccess(array $requestParameters, array $expected): void
    {
        $response = $this->application->makeCreateSourceRequest($this->validToken, $requestParameters);

        $sources = $this->sourceRepository->findAll();
        self::assertIsArray($sources);
        self::assertCount(1, $sources);

        $source = $sources[0];
        self::assertInstanceOf(SourceInterface::class, $source);

        $expected['id'] = $source->getId();
        $expected['user_id'] = $this->authenticationConfiguration->authenticatedUserId;

        $this->responseAsserter->assertSuccessfulJsonResponse($response, $expected);
        $this->requestAsserter->assertAuthorizationRequestIsMade();
    }

    /**
     * @return array<mixed>
     */
    public function createSuccessDataProvider(): array
    {
        $hostUrl = 'https://example.com/repository.git';
        $path = '/';
        $credentials = md5((string) rand());
        $label = 'file source label';

        return [
            'git source, credentials missing' => [
                'requestParameters' => [
                    SourceRequestInterface::PARAMETER_TYPE => Type::GIT->value,
                    GitSourceRequest::PARAMETER_HOST_URL => $hostUrl,
                    GitSourceRequest::PARAMETER_PATH => $path
                ],
                'expected' => [
                    'user_id' => self::AUTHENTICATED_USER_ID_PLACEHOLDER,
                    'type' => Type::GIT->value,
                    'host_url' => $hostUrl,
                    'path' => $path,
                    'has_credentials' => false,
                ],
            ],
            'git source, credentials present' => [
                'requestParameters' => [
                    SourceRequestInterface::PARAMETER_TYPE => Type::GIT->value,
                    GitSourceRequest::PARAMETER_HOST_URL => $hostUrl,
                    GitSourceRequest::PARAMETER_PATH => $path,
                    GitSourceRequest::PARAMETER_CREDENTIALS => $credentials,
                ],
                'expected' => [
                    'user_id' => self::AUTHENTICATED_USER_ID_PLACEHOLDER,
                    'type' => Type::GIT->value,
                    'host_url' => $hostUrl,
                    'path' => $path,
                    'has_credentials' => true,
                ],
            ],
            'file source' => [
                'requestParameters' => [
                    SourceRequestInterface::PARAMETER_TYPE => Type::FILE->value,
                    FileSourceRequest::PARAMETER_LABEL => $label
                ],
                'expected' => [
                    'user_id' => self::AUTHENTICATED_USER_ID_PLACEHOLDER,
                    'type' => Type::FILE->value,
                    'label' => $label,
                ],
            ],
        ];
    }

    public function testListUnauthorizedUser(): void
    {
        $response = $this->application->makeListSourcesRequest($this->invalidToken);

        $this->responseAsserter->assertUnauthorizedResponse($response);
        $this->requestAsserter->assertAuthorizationRequestIsMade($this->invalidToken);
    }

    /**
     * @dataProvider listSuccessDataProvider
     *
     * @param SourceInterface[]        $sources
     * @param array<int, array<mixed>> $expectedResponseData
     */
    public function testListSuccess(array $sources, array $expectedResponseData): void
    {
        foreach ($sources as $source) {
            $source = $this->setSourceUserIdToAuthenticatedUserId($source);
            $this->store->add($source);
        }

        $response = $this->application->makeListSourcesRequest($this->validToken);

        $expectedResponseData = $this->replaceAuthenticatedUserIdInSourceDataCollection($expectedResponseData);

        $this->responseAsserter->assertSuccessfulJsonResponse($response, $expectedResponseData);
        $this->requestAsserter->assertAuthorizationRequestIsMade();
    }

    /**
     * @return array<mixed>
     */
    public function listSuccessDataProvider(): array
    {
        $userFileSources = [
            new FileSource(self::AUTHENTICATED_USER_ID_PLACEHOLDER, 'file source label'),
        ];

        $userGitSources = [
            new GitSource(self::AUTHENTICATED_USER_ID_PLACEHOLDER, 'https://example.com/repository.git'),
        ];

        $userRunSources = [
            new RunSource($userFileSources[0]),
            new RunSource($userGitSources[0]),
        ];

        return [
            'no sources' => [
                'sources' => [],
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
                'expectedResponseData' => [],
            ],
            'has file and git sources for correct user only' => [
                'sources' => [
                    $userFileSources[0],
                    $userGitSources[0],
                ],
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
                'expectedResponseData' => [
                    $userFileSources[0]->jsonSerialize(),
                    $userGitSources[0]->jsonSerialize(),
                ],
            ],
        ];
    }
}
