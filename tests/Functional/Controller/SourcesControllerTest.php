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
use App\Request\AbstractSourceRequest;
use App\Request\FileSourceRequest;
use App\Request\GitSourceRequest;
use App\Services\RunSourceSerializer;
use App\Services\Source\Store;
use App\Tests\Model\Route;
use App\Tests\Model\UserId;
use App\Tests\Services\EntityRemover;
use App\Tests\Services\FileStoreFixtureCreator;
use App\Tests\Services\FixtureLoader;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use SmartAssert\UsersClient\Routes;
use SmartAssert\UsersSecurityBundle\Security\AuthorizationProperties;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\Routing\RouterInterface;
use webignition\HttpHistoryContainer\Container as HttpHistoryContainer;

class SourcesControllerTest extends WebTestCase
{
    private const AUTHORIZATION_TOKEN = 'authorization-token';

    private KernelBrowser $client;
    private MockHandler $mockHandler;
    private HttpHistoryContainer $httpHistoryContainer;
    private SourceRepository $sourceRepository;
    private RunSourceRepository $runSourceRepository;
    private Store $store;
    private RouterInterface $router;
    private FileStoreFixtureCreator $fixtureCreator;
    private FixtureLoader $fixtureLoader;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();

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

        $router = self::getContainer()->get(RouterInterface::class);
        \assert($router instanceof RouterInterface);
        $this->router = $router;

        $fixtureCreator = self::getContainer()->get(FileStoreFixtureCreator::class);
        \assert($fixtureCreator instanceof FileStoreFixtureCreator);
        $this->fixtureCreator = $fixtureCreator;

        $fixtureLoader = self::getContainer()->get(FixtureLoader::class);
        \assert($fixtureLoader instanceof FixtureLoader);
        $this->fixtureLoader = $fixtureLoader;

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

        $response = $this->makeAuthorizedRequest($method, $route);

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
        $response = $this->makeAuthorizedRequest($method, new Route($routeName, $routeParameters));

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
        $response = $this->makeAuthorizedRequest($method, new Route($routeName, $routeParameters));

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

        $response = $this->makeAuthorizedRequest('POST', new Route('create'), $requestParameters);

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
                    AbstractSourceRequest::KEY_POST_TYPE => Type::GIT->value,
                    GitSourceRequest::PARAMETER_HOST_URL => $hostUrl,
                    GitSourceRequest::PARAMETER_PATH => $path
                ],
                'expected' => [
                    'user_id' => $userId,
                    'type' => SourceInterface::TYPE_GIT,
                    'host_url' => $hostUrl,
                    'path' => $path,
                    'has_credentials' => false,
                ],
            ],
            'git source, credentials present' => [
                'userId' => $userId,
                'requestParameters' => [
                    AbstractSourceRequest::KEY_POST_TYPE => Type::GIT->value,
                    GitSourceRequest::PARAMETER_HOST_URL => $hostUrl,
                    GitSourceRequest::PARAMETER_PATH => $path,
                    GitSourceRequest::PARAMETER_CREDENTIALS => $credentials,
                ],
                'expected' => [
                    'user_id' => $userId,
                    'type' => SourceInterface::TYPE_GIT,
                    'host_url' => $hostUrl,
                    'path' => $path,
                    'has_credentials' => true,
                ],
            ],
            'file source' => [
                'userId' => $userId,
                'requestParameters' => [
                    AbstractSourceRequest::KEY_POST_TYPE => Type::FILE->value,
                    FileSourceRequest::PARAMETER_LABEL => $label
                ],
                'expected' => [
                    'user_id' => $userId,
                    'type' => SourceInterface::TYPE_FILE,
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

        $response = $this->makeAuthorizedSourceRequest('GET', 'get', $source->getId());

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
            SourceInterface::TYPE_GIT => [
                'source' => $gitSource,
                'userId' => $userId,
                'expectedResponseData' => [
                    'id' => $gitSource->getId(),
                    'user_id' => $gitSource->getUserId(),
                    'type' => SourceInterface::TYPE_GIT,
                    'host_url' => $gitSource->getHostUrl(),
                    'path' => $gitSource->getPath(),
                    'has_credentials' => true,
                ],
            ],
            SourceInterface::TYPE_FILE => [
                'source' => $fileSource,
                'userId' => $userId,
                'expectedResponseData' => [
                    'id' => $fileSource->getId(),
                    'user_id' => $fileSource->getUserId(),
                    'type' => SourceInterface::TYPE_FILE,
                    'label' => $fileSource->getLabel(),
                ],
            ],
            SourceInterface::TYPE_RUN => [
                'source' => $runSource,
                'userId' => $userId,
                'expectedResponseData' => [
                    'id' => $runSource->getId(),
                    'user_id' => $userId,
                    'type' => SourceInterface::TYPE_RUN,
                    'parent' => $runSource->getParent()?->getId(),
                    'parameters' => [],
                    'state' => State::REQUESTED->value,
                ],
            ],
            SourceInterface::TYPE_RUN . ': preparation failed' => [
                'source' => $failedRunSource,
                'userId' => $userId,
                'expectedResponseData' => [
                    'id' => $failedRunSource->getId(),
                    'user_id' => $userId,
                    'type' => SourceInterface::TYPE_RUN,
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
     * @dataProvider updateSuccessDataProvider
     *
     * @param array<string, string> $requestData
     * @param array<mixed>          $expectedResponseData
     */
    public function testUpdateSuccess(
        SourceInterface $source,
        string $userId,
        array $requestData,
        array $expectedResponseData
    ): void {
        $this->store->add($source);

        $this->mockHandler->append(
            new Response(200, [], $userId)
        );

        $response = $this->makeAuthorizedSourceRequest('PUT', 'update', $source->getId(), $requestData);

        self::assertSame(200, $response->getStatusCode());
        $this->assertAuthorizationRequestIsMade();
        self::assertInstanceOf(JsonResponse::class, $response);

        $responseData = json_decode((string) $response->getContent(), true);
        self::assertEquals($expectedResponseData, $responseData);
    }

    /**
     * @return array<mixed>
     */
    public function updateSuccessDataProvider(): array
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
            SourceInterface::TYPE_FILE => [
                'source' => $fileSource,
                'userId' => $userId,
                'requestData' => [
                    AbstractSourceRequest::KEY_POST_TYPE => Type::FILE->value,
                    FileSourceRequest::PARAMETER_LABEL => $newLabel,
                ],
                'expectedResponseData' => [
                    'id' => $fileSource->getId(),
                    'user_id' => $fileSource->getUserId(),
                    'type' => SourceInterface::TYPE_FILE,
                    'label' => $newLabel,
                ],
            ],
            SourceInterface::TYPE_GIT . ' credentials present and empty' => [
                'source' => $gitSource,
                'userId' => $userId,
                'requestData' => [
                    AbstractSourceRequest::KEY_POST_TYPE => Type::GIT->value,
                    GitSourceRequest::PARAMETER_HOST_URL => $newHostUrl,
                    GitSourceRequest::PARAMETER_PATH => $newPath,
                    GitSourceRequest::PARAMETER_CREDENTIALS => null,
                ],
                'expectedResponseData' => [
                    'id' => $gitSource->getId(),
                    'user_id' => $gitSource->getUserId(),
                    'type' => SourceInterface::TYPE_GIT,
                    'host_url' => $newHostUrl,
                    'path' => $newPath,
                    'has_credentials' => false,
                ],
            ],
            SourceInterface::TYPE_GIT . ' credentials not present' => [
                'source' => $gitSource,
                'userId' => $userId,
                'requestData' => [
                    AbstractSourceRequest::KEY_POST_TYPE => Type::GIT->value,
                    GitSourceRequest::PARAMETER_HOST_URL => $newHostUrl,
                    GitSourceRequest::PARAMETER_PATH => $newPath,
                ],
                'expectedResponseData' => [
                    'id' => $gitSource->getId(),
                    'user_id' => $gitSource->getUserId(),
                    'type' => SourceInterface::TYPE_GIT,
                    'host_url' => $newHostUrl,
                    'path' => $newPath,
                    'has_credentials' => false,
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

        $response = $this->makeAuthorizedSourceRequest('DELETE', 'delete', $source->getId());

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
            SourceInterface::TYPE_FILE => [
                'source' => new FileSource($userId, 'label'),
                'userId' => $userId,
                'expectedRepositoryCount' => 0,
            ],
            SourceInterface::TYPE_GIT => [
                'source' => new GitSource($userId, 'https://example.com/repository.git'),
                'userId' => $userId,
                'expectedRepositoryCount' => 0,
            ],
            SourceInterface::TYPE_RUN => [
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

        $response = $this->makeAuthorizedRequest('GET', new Route('list'));

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

        $response = $this->makeAuthorizedSourceRequest('POST', 'prepare', $source->getId());

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

        $response = $this->makeAuthorizedSourceRequest(
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
            SourceInterface::TYPE_FILE => [
                'source' => $fileSource,
                'userId' => $userId,
                'requestParameters' => [],
                'expectedResponseData' => [
                    'id' => '{{ runSourceId }}',
                    'user_id' => $gitSource->getUserId(),
                    'type' => SourceInterface::TYPE_RUN,
                    'parent' => $fileSource->getId(),
                    'parameters' => [],
                    'state' => State::REQUESTED->value,
                ],
            ],
            SourceInterface::TYPE_GIT => [
                'source' => $gitSource,
                'userId' => $userId,
                'requestParameters' => [],
                'expectedResponseData' => [
                    'id' => '{{ runSourceId }}',
                    'user_id' => $gitSource->getUserId(),
                    'type' => SourceInterface::TYPE_RUN,
                    'parent' => $gitSource->getId(),
                    'parameters' => [],
                    'state' => State::REQUESTED->value,
                ],
            ],
            SourceInterface::TYPE_GIT . ' with ref request parameters' => [
                'source' => $gitSource,
                'userId' => $userId,
                'requestParameters' => [
                    'ref' => 'v1.1',
                ],
                'expectedResponseData' => [
                    'id' => '{{ runSourceId }}',
                    'user_id' => $gitSource->getUserId(),
                    'type' => SourceInterface::TYPE_RUN,
                    'parent' => $gitSource->getId(),
                    'parameters' => [
                        'ref' => 'v1.1',
                    ],
                    'state' => State::REQUESTED->value,
                ],
            ],
            SourceInterface::TYPE_GIT . ' with request parameters including ref' => [
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
                    'type' => SourceInterface::TYPE_RUN,
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

        $response = $this->makeAuthorizedSourceRequest(
            'GET',
            'read',
            $runSource->getId()
        );

        self::assertSame($expectedResponse->getStatusCode(), $response->getStatusCode());
        self::assertSame($expectedResponse->headers->get('content-type'), $response->headers->get('content-type'));
        self::assertSame($expectedResponse->getContent(), $response->getContent());
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

    /**
     * @return array<string, string>
     */
    private function createAuthorizationHeader(): array
    {
        $authHeaderName = AuthorizationProperties::DEFAULT_HEADER_NAME;
        $authHeaderValue = AuthorizationProperties::DEFAULT_VALUE_PREFIX . self::AUTHORIZATION_TOKEN;

        return [
            $authHeaderName => $authHeaderValue,
        ];
    }

    /**
     * @param array<string, string> $headers
     *
     * @return array<string, string>
     */
    private function createRequestServerPropertiesFromHeaders(array $headers): array
    {
        $server = [];
        foreach ($headers as $key => $value) {
            $server['HTTP_' . $key] = $value;
        }

        return $server;
    }

    /**
     * @param array<string, string> $parameters
     */
    private function makeAuthorizedSourceRequest(
        string $method,
        string $routeName,
        string $sourceId,
        array $parameters = []
    ): SymfonyResponse {
        return $this->makeAuthorizedRequest($method, new Route($routeName, ['sourceId' => $sourceId]), $parameters);
    }

    /**
     * @param array<string, string> $parameters
     */
    private function makeAuthorizedRequest(string $method, Route $route, array $parameters = []): SymfonyResponse
    {
        return $this->makeRequest($method, $route, $this->createAuthorizationHeader(), $parameters);
    }

    /**
     * @param array<string, string> $headers
     * @param array<string, string> $parameters
     */
    private function makeRequest(string $method, Route $route, array $headers, array $parameters): SymfonyResponse
    {
        $this->client->request(
            method: $method,
            uri: $this->router->generate($route->name, $route->parameters),
            parameters: $parameters,
            server: $this->createRequestServerPropertiesFromHeaders($headers)
        );

        return $this->client->getResponse();
    }
}
