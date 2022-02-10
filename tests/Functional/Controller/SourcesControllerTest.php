<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Entity\SourceInterface;
use App\Enum\RunSource\FailureReason;
use App\Enum\RunSource\State;
use App\Enum\Source\Type;
use App\Model\EntityId;
use App\Repository\RunSourceRepository;
use App\Repository\SourceRepository;
use App\Request\FileSourceRequest;
use App\Request\GitSourceRequest;
use App\Request\InvalidSourceTypeRequest;
use App\Request\SourceRequestInterface;
use App\Services\RunSourceSerializer;
use App\Services\Source\Store;
use App\Tests\Model\Route;
use App\Tests\Model\UserId;
use App\Tests\Services\EntityRemover;
use App\Tests\Services\FileStoreFixtureCreator;
use App\Tests\Services\FixtureLoader;
use App\Tests\Services\ApplicationClient;
use App\Validator\YamlFileConstraint;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use SmartAssert\UsersClient\Routes;
use SmartAssert\UsersSecurityBundle\Security\AuthorizationProperties;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use webignition\HttpHistoryContainer\Container as HttpHistoryContainer;

class SourcesControllerTest extends WebTestCase
{
    private const AUTHORIZATION_TOKEN = 'authorization-token';

    private MockHandler $mockHandler;
    private HttpHistoryContainer $httpHistoryContainer;
    private SourceRepository $sourceRepository;
    private RunSourceRepository $runSourceRepository;
    private Store $store;
    private FileStoreFixtureCreator $fixtureCreator;
    private FixtureLoader $fixtureLoader;
    private ApplicationClient $applicationClient;

    protected function setUp(): void
    {
        parent::setUp();

        $client = static::createClient();

        $mockHandler = self::getContainer()->get(MockHandler::class);
        \assert($mockHandler instanceof MockHandler);
        $this->mockHandler = $mockHandler;

        $httpHistoryContainer = self::getContainer()->get(HttpHistoryContainer::class);
        \assert($httpHistoryContainer instanceof HttpHistoryContainer);
        $this->httpHistoryContainer = $httpHistoryContainer;

        $handlerStack = self::getContainer()->get(HandlerStack::class);
        \assert($handlerStack instanceof HandlerStack);
        $handlerStack->push(Middleware::history($this->httpHistoryContainer), 'history');

        $store = self::getContainer()->get(Store::class);
        \assert($store instanceof Store);
        $this->store = $store;

        $sourceRepository = self::getContainer()->get(SourceRepository::class);
        \assert($sourceRepository instanceof SourceRepository);
        $this->sourceRepository = $sourceRepository;

        $runSourceRepository = self::getContainer()->get(RunSourceRepository::class);
        \assert($runSourceRepository instanceof RunSourceRepository);
        $this->runSourceRepository = $runSourceRepository;

        $fixtureCreator = self::getContainer()->get(FileStoreFixtureCreator::class);
        \assert($fixtureCreator instanceof FileStoreFixtureCreator);
        $this->fixtureCreator = $fixtureCreator;

        $fixtureLoader = self::getContainer()->get(FixtureLoader::class);
        \assert($fixtureLoader instanceof FixtureLoader);
        $this->fixtureLoader = $fixtureLoader;

        $applicationClient = self::getContainer()->get(ApplicationClient::class);
        \assert($applicationClient instanceof ApplicationClient);
        $applicationClient->setClient($client);

        $this->applicationClient = $applicationClient;

        $entityRemover = self::getContainer()->get(EntityRemover::class);
        if ($entityRemover instanceof EntityRemover) {
            $entityRemover->removeAll();
        }
    }

    /**
     * @dataProvider requestForUnauthorizedUserDataProvider
     */
    public function testRequestForUnauthorizedUser(string $method, Route $route): void
    {
        $this->mockHandler->append(
            new Response(401)
        );

        $response = $this->applicationClient->makeAuthorizedRequest($method, $route);

        self::assertSame(401, $response->getStatusCode());
        $this->assertAuthorizationRequestIsMade();
    }

    /**
     * @return array<mixed>
     */
    public function requestForUnauthorizedUserDataProvider(): array
    {
        $sourceRouteParameters = ['sourceId' => EntityId::create()];

        return [
            'create source' => [
                'method' => 'POST',
                'route' => new Route('create'),
            ],
            'get source' => [
                'method' => 'GET',
                'route' => new Route('get', $sourceRouteParameters),
            ],
            'update source' => [
                'method' => 'PUT',
                'route' => new Route('update', $sourceRouteParameters),
            ],
            'delete source' => [
                'method' => 'DELETE',
                'route' => new Route('delete', $sourceRouteParameters),
            ],
            'list sources' => [
                'method' => 'GET',
                'route' => new Route('list'),
            ],
            'prepare source' => [
                'method' => 'POST',
                'route' => new Route('prepare', $sourceRouteParameters),
            ],
        ];
    }

    /**
     * @dataProvider requestSourceDataProvider
     */
    public function testRequestSourceNotFound(string $method, string $routeName): void
    {
        $sourceId = EntityId::create();

        $this->mockHandler->append(
            new Response(200, [], $sourceId)
        );

        $routeParameters = ['sourceId' => $sourceId];
        $response = $this->applicationClient->makeAuthorizedRequest($method, new Route($routeName, $routeParameters));

        self::assertSame(404, $response->getStatusCode());
    }

    /**
     * @dataProvider requestSourceDataProvider
     */
    public function testRequestInvalidSourceUser(string $method, string $routeName): void
    {
        $sourceUserId = UserId::create();
        $requestUserId = UserId::create();
        $label = 'source label';

        $source = new FileSource($sourceUserId, $label);
        $sourceId = $source->getId();
        $this->store->add($source);

        $this->mockHandler->append(
            new Response(200, [], $requestUserId)
        );

        $routeParameters = ['sourceId' => $sourceId];
        $response = $this->applicationClient->makeAuthorizedRequest($method, new Route($routeName, $routeParameters));

        self::assertSame(401, $response->getStatusCode());
    }

    /**
     * @return array<mixed>
     */
    public function requestSourceDataProvider(): array
    {
        return [
            'get source' => [
                'method' => 'GET',
                'routeName' => 'get',
            ],
            'update source' => [
                'method' => 'PUT',
                'routeName' => 'update',
            ],
            'delete source' => [
                'method' => 'DELETE',
                'routeName' => 'delete',
            ],
            'prepare source' => [
                'method' => 'POST',
                'routeName' => 'prepare',
            ],
        ];
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
        $this->mockHandler->append(
            new Response(200, [], $userId)
        );

        $response = $this->applicationClient->makeAuthorizedRequest('POST', new Route('create'), $requestParameters);

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
        $this->mockHandler->append(
            new Response(200, [], $userId)
        );

        $response = $this->applicationClient->makeAuthorizedRequest('POST', new Route('create'), $requestParameters);

        self::assertSame(200, $response->getStatusCode());
        $this->assertAuthorizationRequestIsMade();

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
     * @dataProvider getSuccessDataProvider
     *
     * @param array<mixed> $expectedResponseData
     */
    public function testGetSuccess(SourceInterface $source, string $userId, array $expectedResponseData): void
    {
        $this->store->add($source);

        $this->mockHandler->append(
            new Response(200, [], $userId)
        );

        $response = $this->applicationClient->makeAuthorizedSourceRequest('GET', 'get', $source->getId());

        self::assertSame(200, $response->getStatusCode());
        $this->assertAuthorizationRequestIsMade();
        self::assertInstanceOf(JsonResponse::class, $response);

        $responseData = json_decode((string) $response->getContent(), true);
        self::assertEquals($expectedResponseData, $responseData);
    }

    /**
     * @return array<mixed>
     */
    public function getSuccessDataProvider(): array
    {
        $userId = UserId::create();

        $gitSource = new GitSource($userId, 'https://example.com/repository.git', '/', md5((string) rand()));
        $fileSource = new FileSource($userId, 'file source label');
        $runSource = new RunSource($fileSource);

        $failureMessage = 'fatal: repository \'http://example.com/repository.git\' not found';
        $failedRunSource = (new RunSource($gitSource))->setPreparationFailed(
            FailureReason::GIT_CLONE,
            $failureMessage
        );

        return [
            Type::GIT->value => [
                'source' => $gitSource,
                'userId' => $userId,
                'expectedResponseData' => [
                    'id' => $gitSource->getId(),
                    'user_id' => $gitSource->getUserId(),
                    'type' => Type::GIT->value,
                    'host_url' => $gitSource->getHostUrl(),
                    'path' => $gitSource->getPath(),
                    'has_credentials' => true,
                ],
            ],
            Type::FILE->value => [
                'source' => $fileSource,
                'userId' => $userId,
                'expectedResponseData' => [
                    'id' => $fileSource->getId(),
                    'user_id' => $fileSource->getUserId(),
                    'type' => Type::FILE->value,
                    'label' => $fileSource->getLabel(),
                ],
            ],
            Type::RUN->value => [
                'source' => $runSource,
                'userId' => $userId,
                'expectedResponseData' => [
                    'id' => $runSource->getId(),
                    'user_id' => $userId,
                    'type' => Type::RUN->value,
                    'parent' => $runSource->getParent()?->getId(),
                    'parameters' => [],
                    'state' => State::REQUESTED->value,
                ],
            ],
            Type::RUN->value . ': preparation failed' => [
                'source' => $failedRunSource,
                'userId' => $userId,
                'expectedResponseData' => [
                    'id' => $failedRunSource->getId(),
                    'user_id' => $userId,
                    'type' => Type::RUN->value,
                    'parent' => $failedRunSource->getParent()?->getId(),
                    'parameters' => [],
                    'state' => State::FAILED->value,
                    'failure_reason' => FailureReason::GIT_CLONE->value,
                    'failure_message' => $failureMessage,
                ],
            ],
        ];
    }

    /**
     * @dataProvider updateDataProvider
     *
     * @param array<string, string> $requestData
     * @param array<mixed>          $expectedResponseData
     */
    public function testUpdate(
        SourceInterface $source,
        string $userId,
        array $requestData,
        int $expectedResponseStatusCode,
        array $expectedResponseData
    ): void {
        $this->store->add($source);

        $this->mockHandler->append(
            new Response(200, [], $userId)
        );

        $response = $this->applicationClient->makeAuthorizedSourceRequest(
            'PUT',
            'update',
            $source->getId(),
            $requestData
        );

        self::assertSame($expectedResponseStatusCode, $response->getStatusCode());
        $this->assertAuthorizationRequestIsMade();
        self::assertInstanceOf(JsonResponse::class, $response);

        $responseData = json_decode((string) $response->getContent(), true);
        self::assertEquals($expectedResponseData, $responseData);
    }

    /**
     * @return array<mixed>
     */
    public function updateDataProvider(): array
    {
        $userId = UserId::create();
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
                'userId' => $userId,
                'requestData' => [
                    SourceRequestInterface::PARAMETER_TYPE => Type::FILE->value,
                    FileSourceRequest::PARAMETER_LABEL => $newLabel,
                ],
                'expectedResponseStatusCode' => 200,
                'expectedResponseData' => [
                    'id' => $fileSource->getId(),
                    'user_id' => $fileSource->getUserId(),
                    'type' => Type::FILE->value,
                    'label' => $newLabel,
                ],
            ],
            Type::GIT->value . ' credentials present and empty' => [
                'source' => $gitSource,
                'userId' => $userId,
                'requestData' => [
                    SourceRequestInterface::PARAMETER_TYPE => Type::GIT->value,
                    GitSourceRequest::PARAMETER_HOST_URL => $newHostUrl,
                    GitSourceRequest::PARAMETER_PATH => $newPath,
                    GitSourceRequest::PARAMETER_CREDENTIALS => null,
                ],
                'expectedResponseStatusCode' => 200,
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
                'userId' => $userId,
                'requestData' => [
                    SourceRequestInterface::PARAMETER_TYPE => Type::GIT->value,
                    GitSourceRequest::PARAMETER_HOST_URL => $newHostUrl,
                    GitSourceRequest::PARAMETER_PATH => $newPath,
                ],
                'expectedResponseStatusCode' => 200,
                'expectedResponseData' => [
                    'id' => $gitSource->getId(),
                    'user_id' => $gitSource->getUserId(),
                    'type' => Type::GIT->value,
                    'host_url' => $newHostUrl,
                    'path' => $newPath,
                    'has_credentials' => false,
                ],
            ],
            Type::GIT->value . ' missing host url' => [
                'source' => $gitSource,
                'userId' => $userId,
                'requestData' => [
                    SourceRequestInterface::PARAMETER_TYPE => Type::GIT->value,
                    GitSourceRequest::PARAMETER_HOST_URL => '',
                    GitSourceRequest::PARAMETER_PATH => $path,
                ],
                'expectedResponseStatusCode' => 400,
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
     * @dataProvider deleteSuccessDataProvider
     */
    public function testDeleteSuccess(SourceInterface $source, string $userId, int $expectedRepositoryCount): void
    {
        $this->store->add($source);
        self::assertGreaterThan(0, $this->sourceRepository->count([]));

        $this->mockHandler->append(
            new Response(200, [], $userId)
        );

        $response = $this->applicationClient->makeAuthorizedSourceRequest('DELETE', 'delete', $source->getId());

        self::assertSame(200, $response->getStatusCode());
        $this->assertAuthorizationRequestIsMade();
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame($expectedRepositoryCount, $this->sourceRepository->count([]));
    }

    /**
     * @return array<mixed>
     */
    public function deleteSuccessDataProvider(): array
    {
        $userId = UserId::create();

        return [
            Type::FILE->value => [
                'source' => new FileSource($userId, 'label'),
                'userId' => $userId,
                'expectedRepositoryCount' => 0,
            ],
            Type::GIT->value => [
                'source' => new GitSource($userId, 'https://example.com/repository.git'),
                'userId' => $userId,
                'expectedRepositoryCount' => 0,
            ],
            Type::RUN->value => [
                'source' => new RunSource(
                    new FileSource($userId, 'label')
                ),
                'userId' => $userId,
                'expectedRepositoryCount' => 1,
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

        $this->mockHandler->append(
            new Response(200, [], $userId)
        );

        $response = $this->applicationClient->makeAuthorizedRequest('GET', new Route('list'));

        self::assertSame(200, $response->getStatusCode());
        $this->assertAuthorizationRequestIsMade();
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

    public function testPrepareRunSource(): void
    {
        $userId = UserId::create();

        $fileSource = new FileSource($userId, 'file source label');
        $source = new RunSource($fileSource);

        $this->store->add($source);

        $this->mockHandler->append(
            new Response(200, [], $userId)
        );

        $response = $this->applicationClient->makeAuthorizedSourceRequest('POST', 'prepare', $source->getId());

        self::assertSame(404, $response->getStatusCode());
        $this->assertAuthorizationRequestIsMade();
    }

    /**
     * @dataProvider prepareSuccessDataProvider
     *
     * @param array<string, string> $requestParameters
     * @param array<mixed>          $expectedResponseData
     */
    public function testPrepareSuccess(
        FileSource|GitSource $source,
        string $userId,
        array $requestParameters,
        array $expectedResponseData
    ): void {
        $this->store->add($source);

        $this->mockHandler->append(
            new Response(200, [], $userId)
        );

        $response = $this->applicationClient->makeAuthorizedSourceRequest(
            'POST',
            'prepare',
            $source->getId(),
            $requestParameters
        );

        self::assertSame(202, $response->getStatusCode());
        $this->assertAuthorizationRequestIsMade();
        self::assertInstanceOf(JsonResponse::class, $response);

        $responseData = json_decode((string) $response->getContent(), true);
        self::assertIsArray($responseData);

        $runSource = $this->runSourceRepository->findByParent($source);
        self::assertInstanceOf(RunSource::class, $runSource);

        $expectedResponseData['id'] = $runSource->getId();
        self::assertSame($expectedResponseData, $responseData);
    }

    /**
     * @return array<mixed>
     */
    public function prepareSuccessDataProvider(): array
    {
        $userId = UserId::create();

        $fileSource = new FileSource($userId, 'file source label');
        $gitSource = new GitSource($userId, 'https://example.com/repository.git', '/', md5((string) rand()));

        return [
            Type::FILE->value => [
                'source' => $fileSource,
                'userId' => $userId,
                'requestParameters' => [],
                'expectedResponseData' => [
                    'id' => '{{ runSourceId }}',
                    'user_id' => $gitSource->getUserId(),
                    'type' => Type::RUN->value,
                    'parent' => $fileSource->getId(),
                    'parameters' => [],
                    'state' => State::REQUESTED->value,
                ],
            ],
            Type::GIT->value => [
                'source' => $gitSource,
                'userId' => $userId,
                'requestParameters' => [],
                'expectedResponseData' => [
                    'id' => '{{ runSourceId }}',
                    'user_id' => $gitSource->getUserId(),
                    'type' => Type::RUN->value,
                    'parent' => $gitSource->getId(),
                    'parameters' => [],
                    'state' => State::REQUESTED->value,
                ],
            ],
            Type::GIT->value . ' with ref request parameters' => [
                'source' => $gitSource,
                'userId' => $userId,
                'requestParameters' => [
                    'ref' => 'v1.1',
                ],
                'expectedResponseData' => [
                    'id' => '{{ runSourceId }}',
                    'user_id' => $gitSource->getUserId(),
                    'type' => Type::RUN->value,
                    'parent' => $gitSource->getId(),
                    'parameters' => [
                        'ref' => 'v1.1',
                    ],
                    'state' => State::REQUESTED->value,
                ],
            ],
            Type::GIT->value . ' with request parameters including ref' => [
                'source' => $gitSource,
                'userId' => $userId,
                'requestParameters' => [
                    'ref' => 'v1.1',
                    'ignored1' => 'value',
                    'ignored2' => 'value',
                ],
                'expectedResponseData' => [
                    'id' => '{{ runSourceId }}',
                    'user_id' => $gitSource->getUserId(),
                    'type' => Type::RUN->value,
                    'parent' => $gitSource->getId(),
                    'parameters' => [
                        'ref' => 'v1.1',
                    ],
                    'state' => State::REQUESTED->value,
                ],
            ],
        ];
    }

    public function testReadSuccess(): void
    {
        $serializedRunSourceFixturePath = '/RunSource/source_yml_yaml_entire.yaml';

        $expectedResponse = new SymfonyResponse(
            $this->fixtureLoader->load($serializedRunSourceFixturePath),
            200,
            [
                'content-type' => 'text/x-yaml; charset=utf-8',
            ]
        );

        $userId = UserId::create();

        $fileSource = new FileSource($userId, 'file source label');
        $runSource = new RunSource($fileSource);
        $this->store->add($runSource);

        $this->fixtureCreator->copyTo(
            $serializedRunSourceFixturePath,
            $runSource . '/' . RunSourceSerializer::SERIALIZED_FILENAME
        );

        $this->mockHandler->append(
            new Response(200, [], $userId)
        );

        $response = $this->applicationClient->makeAuthorizedSourceRequest('GET', 'read', $runSource->getId());

        self::assertSame($expectedResponse->getStatusCode(), $response->getStatusCode());
        self::assertSame($expectedResponse->headers->get('content-type'), $response->headers->get('content-type'));
        self::assertSame($expectedResponse->getContent(), $response->getContent());
    }

    /**
     * @dataProvider addFileInvalidRequestDataProvider
     *
     * @param array<string, string> $requestData
     * @param array<mixed>          $expectedResponseData
     */
    public function testAddFileInvalidRequest(
        SourceInterface $source,
        string $userId,
        array $requestData,
        array $expectedResponseData
    ): void {
        $this->store->add($source);

        $this->mockHandler->append(
            new Response(200, [], $userId)
        );

        $response = $this->applicationClient->makeAuthorizedSourceRequest(
            'POST',
            'add_file',
            $source->getId(),
            $requestData
        );

        self::assertSame(400, $response->getStatusCode());
        $this->assertAuthorizationRequestIsMade();
        self::assertInstanceOf(JsonResponse::class, $response);

        $responseData = json_decode((string) $response->getContent(), true);
        self::assertEquals($expectedResponseData, $responseData);
    }

    /**
     * @return array<mixed>
     */
    public function addFileInvalidRequestDataProvider(): array
    {
        $userId = UserId::create();
        $label = 'file source label';

        $fileSource = new FileSource($userId, $label);

        return [
            'name empty, content non-empty' => [
                'source' => $fileSource,
                'userId' => $userId,
                'requestData' => [
                    'name' => '',
                    'content' => 'non-empty value',
                ],
                'expectedResponseData' => [
                    'error' => [
                        'type' => 'invalid_request',
                        'payload' => [
                            'name' => [
                                'value' => '',
                                'message' => YamlFileConstraint::MESSAGE_NAME_INVALID,
                            ],
                        ],
                    ],
                ],
            ],
            'name contains backslash characters, content non-empty' => [
                'source' => $fileSource,
                'userId' => $userId,
                'requestData' => [
                    'name' => 'one two \\ three',
                    'content' => 'non-empty value',
                ],
                'expectedResponseData' => [
                    'error' => [
                        'type' => 'invalid_request',
                        'payload' => [
                            'name' => [
                                'value' => '',
                                'message' => YamlFileConstraint::MESSAGE_NAME_INVALID,
                            ],
                        ],
                    ],
                ],
            ],
            'name contains null byte characters, content non-empty' => [
                'source' => $fileSource,
                'userId' => $userId,
                'requestData' => [
                    'name' => 'one ' . chr(0) . ' two three' . chr(0),
                    'content' => 'non-empty value',
                ],
                'expectedResponseData' => [
                    'error' => [
                        'type' => 'invalid_request',
                        'payload' => [
                            'name' => [
                                'value' => '',
                                'message' => YamlFileConstraint::MESSAGE_NAME_INVALID,
                            ],
                        ],
                    ],
                ],
            ],
            'name does not end with .yml or .yaml, content non-empty' => [
                'source' => $fileSource,
                'userId' => $userId,
                'requestData' => [
                    'name' => 'filename',
                    'content' => 'non-empty value',
                ],
                'expectedResponseData' => [
                    'error' => [
                        'type' => 'invalid_request',
                        'payload' => [
                            'name' => [
                                'value' => '',
                                'message' => YamlFileConstraint::MESSAGE_NAME_INVALID,
                            ],
                        ],
                    ],
                ],
            ],
            'name valid, content empty' => [
                'source' => $fileSource,
                'userId' => $userId,
                'requestData' => [
                    'name' => 'filename.yaml',
                    'content' => '',
                ],
                'expectedResponseData' => [
                    'error' => [
                        'type' => 'invalid_request',
                        'payload' => [
                            'content' => [
                                'value' => '',
                                'message' => 'File content must not be empty.',
                            ],
                        ],
                    ],
                ],
            ],
            'name valid, content invalid yaml' => [
                'source' => $fileSource,
                'userId' => $userId,
                'requestData' => [
                    'name' => 'filename.yml',
                    'content' => "- item\ncontent",
                ],
                'expectedResponseData' => [
                    'error' => [
                        'type' => 'invalid_request',
                        'payload' => [
                            'content' => [
                                'value' => '',
                                'message' => 'Content must be valid YAML: Unable to parse at line 2 (near "content").',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function assertAuthorizationRequestIsMade(): void
    {
        $request = $this->httpHistoryContainer->getTransactions()->getRequests()->getLast();
        \assert($request instanceof RequestInterface);

        $usersServiceBaseUrl = self::getContainer()->getParameter('users_security_bundle_base_url');
        \assert(is_string($usersServiceBaseUrl));

        $expectedUrl = $usersServiceBaseUrl . Routes::DEFAULT_VERIFY_API_TOKEN_PATH;

        self::assertSame($expectedUrl, (string) $request->getUri());

        $authorizationHeader = $request->getHeaderLine(AuthorizationProperties::DEFAULT_HEADER_NAME);

        $expectedAuthorizationHeader = AuthorizationProperties::DEFAULT_VALUE_PREFIX . self::AUTHORIZATION_TOKEN;

        self::assertSame($expectedAuthorizationHeader, $authorizationHeader);
    }
}
