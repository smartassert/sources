<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Controller\SourceController;
use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Entity\SourceInterface;
use App\Model\EntityId;
use App\Repository\FileSourceRepository;
use App\Repository\GitSourceRepository;
use App\Repository\SourceRepository;
use App\Request\FileSourceRequest;
use App\Request\GitSourceRequest;
use App\Services\Source\Store;
use App\Tests\Model\UserId;
use App\Tests\Services\Source\SourceRemover;
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
use webignition\HttpHistoryContainer\Container as HttpHistoryContainer;

class SourcesControllerTest extends WebTestCase
{
    private const AUTHORIZATION_TOKEN = 'authorization-token';

    private KernelBrowser $client;
    private MockHandler $mockHandler;
    private HttpHistoryContainer $httpHistoryContainer;
    private SourceRepository $repository;
    private Store $store;

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

        $repository = self::getContainer()->get(SourceRepository::class);
        \assert($repository instanceof SourceRepository);
        $this->repository = $repository;

        $sourceRemover = self::getContainer()->get(SourceRemover::class);
        if ($sourceRemover instanceof SourceRemover) {
            $sourceRemover->removeAll();
        }
    }

    /**
     * @dataProvider requestForUnauthorizedUserDataProvider
     */
    public function testRequestForUnauthorizedUser(string $method, string $uri): void
    {
        $this->mockHandler->append(
            new Response(401)
        );

        $this->client->request(
            method: $method,
            uri: $uri,
            server: $this->createRequestServerPropertiesFromHeaders(
                $this->createAuthorizationHeader()
            )
        );

        $response = $this->client->getResponse();

        self::assertSame(401, $response->getStatusCode());
        $this->assertAuthorizationRequestIsMade();
    }

    /**
     * @return array<mixed>
     */
    public function requestForUnauthorizedUserDataProvider(): array
    {
        return [
            'create git source' => [
                'method' => 'POST',
                'uri' => SourceController::ROUTE_GIT_SOURCE_CREATE,
            ],
            'create file source' => [
                'method' => 'POST',
                'uri' => SourceController::ROUTE_FILE_SOURCE_CREATE,
            ],
            'get source' => [
                'method' => 'GET',
                'uri' => SourceController::ROUTE_SOURCE . EntityId::create(),
            ],
            'update source' => [
                'method' => 'PUT',
                'uri' => SourceController::ROUTE_SOURCE . EntityId::create(),
            ],
            'delete source' => [
                'method' => 'DELETE',
                'uri' => SourceController::ROUTE_SOURCE . EntityId::create(),
            ],
            'list sources' => [
                'method' => 'GET',
                'uri' => SourceController::ROUTE_SOURCE_LIST,
            ],
        ];
    }

    /**
     * @dataProvider requestSourceDataProvider
     */
    public function testRequestSourceNotFound(string $method): void
    {
        $sourceId = EntityId::create();

        $this->mockHandler->append(
            new Response(200, [], $sourceId)
        );

        $response = $this->makeAuthorizedSourceRequest($method, $sourceId);
        self::assertSame(404, $response->getStatusCode());
    }

    /**
     * @dataProvider requestSourceDataProvider
     */
    public function testRequestInvalidSourceUser(string $method): void
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

        $response = $this->makeAuthorizedSourceRequest($method, $sourceId);
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
            ],
            'update source' => [
                'method' => 'PUT',
            ],
            'delete source' => [
                'method' => 'DELETE',
            ],
        ];
    }

    /**
     * @dataProvider createGitSourceDataProvider
     *
     * @param array<string, string> $requestParameters
     * @param array<mixed>          $expected
     */
    public function testCreateGitSourceSuccess(string $userId, array $requestParameters, array $expected): void
    {
        $this->mockHandler->append(
            new Response(200, [], $userId)
        );

        $response = $this->makeAuthorizedRequest(
            'POST',
            SourceController::ROUTE_GIT_SOURCE_CREATE,
            $requestParameters
        );

        self::assertSame(200, $response->getStatusCode());
        $this->assertAuthorizationRequestIsMade();

        $repository = self::getContainer()->get(GitSourceRepository::class);
        \assert($repository instanceof GitSourceRepository);

        $source = $repository->findOneBy([
            'userId' => $userId,
            'hostUrl' => $requestParameters[GitSourceRequest::KEY_POST_HOST_URL],
            'path' => $requestParameters[GitSourceRequest::KEY_POST_PATH],
            'credentials' => $requestParameters[GitSourceRequest::KEY_POST_CREDENTIALS] ?? null,
        ]);

        self::assertInstanceOf(SourceInterface::class, $source);
        \assert($source instanceof SourceInterface);
        $expected['id'] = $source->getId();

        self::assertEquals($expected, json_decode((string) $response->getContent(), true));
    }

    /**
     * @return array<mixed>
     */
    public function createGitSourceDataProvider(): array
    {
        $userId = UserId::create();
        $hostUrl = 'https://example.com/repository.git';
        $path = '/';
        $credentials = md5((string) rand());

        return [
            'credentials missing' => [
                'userId' => $userId,
                'requestParameters' => [
                    GitSourceRequest::KEY_POST_HOST_URL => $hostUrl,
                    GitSourceRequest::KEY_POST_PATH => $path
                ],
                'expected' => [
                    'user_id' => $userId,
                    'type' => SourceInterface::TYPE_GIT,
                    'host_url' => $hostUrl,
                    'path' => $path,
                    'has_credentials' => false,
                ],
            ],
            'credentials present' => [
                'userId' => $userId,
                'requestParameters' => [
                    GitSourceRequest::KEY_POST_HOST_URL => $hostUrl,
                    GitSourceRequest::KEY_POST_PATH => $path,
                    GitSourceRequest::KEY_POST_CREDENTIALS => $credentials,
                ],
                'expected' => [
                    'user_id' => $userId,
                    'type' => SourceInterface::TYPE_GIT,
                    'host_url' => $hostUrl,
                    'path' => $path,
                    'has_credentials' => true,
                ],
            ],
        ];
    }

    /**
     * @dataProvider createFileSourceDataProvider
     *
     * @param array<string, string> $requestParameters
     * @param array<mixed>          $expected
     */
    public function testCreateFileSourceSuccess(string $userId, array $requestParameters, array $expected): void
    {
        $this->mockHandler->append(
            new Response(200, [], $userId)
        );

        $response = $this->makeAuthorizedRequest(
            'POST',
            SourceController::ROUTE_FILE_SOURCE_CREATE,
            $requestParameters
        );

        self::assertSame(200, $response->getStatusCode());
        $this->assertAuthorizationRequestIsMade();

        $repository = self::getContainer()->get(FileSourceRepository::class);
        \assert($repository instanceof FileSourceRepository);

        $source = $repository->findOneBy([
            'userId' => $userId,
            'label' => $requestParameters[FileSourceRequest::KEY_POST_LABEL],
        ]);

        self::assertInstanceOf(SourceInterface::class, $source);
        \assert($source instanceof SourceInterface);
        $expected['id'] = $source->getId();

        self::assertEquals($expected, json_decode((string) $response->getContent(), true));
    }

    /**
     * @return array<mixed>
     */
    public function createFileSourceDataProvider(): array
    {
        $userId = UserId::create();
        $label = 'file source label';

        return [
            'default' => [
                'userId' => $userId,
                'requestParameters' => [
                    FileSourceRequest::KEY_POST_LABEL => $label
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

        $response = $this->makeAuthorizedSourceRequest('GET', $source->getId());

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

        $response = $this->makeAuthorizedSourceRequest('PUT', $source->getId(), $requestData);

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
                    FileSourceRequest::KEY_POST_LABEL => $newLabel,
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
                    GitSourceRequest::KEY_POST_HOST_URL => $newHostUrl,
                    GitSourceRequest::KEY_POST_PATH => $newPath,
                    GitSourceRequest::KEY_POST_CREDENTIALS => null,
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
                    GitSourceRequest::KEY_POST_HOST_URL => $newHostUrl,
                    GitSourceRequest::KEY_POST_PATH => $newPath,
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
        self::assertGreaterThan(0, $this->repository->count([]));

        $this->mockHandler->append(
            new Response(200, [], $userId)
        );

        $response = $this->makeAuthorizedSourceRequest('DELETE', $source->getId());

        self::assertSame(200, $response->getStatusCode());
        $this->assertAuthorizationRequestIsMade();
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame($expectedRepositoryCount, $this->repository->count([]));
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

        $response = $this->makeAuthorizedRequest('GET', SourceController::ROUTE_SOURCE_LIST);

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
        string $sourceId,
        array $parameters = []
    ): SymfonyResponse {
        return $this->makeAuthorizedRequest($method, SourceController::ROUTE_SOURCE . $sourceId, $parameters);
    }

    /**
     * @param array<string, string> $parameters
     */
    private function makeAuthorizedRequest(string $method, string $uri, array $parameters = []): SymfonyResponse
    {
        return $this->makeRequest($method, $uri, $this->createAuthorizationHeader(), $parameters);
    }

    /**
     * @param array<string, string> $headers
     * @param array<string, string> $parameters
     */
    private function makeRequest(
        string $method,
        string $uri,
        array $headers = [],
        array $parameters = []
    ): SymfonyResponse {
        $this->client->request(
            method: $method,
            uri: $uri,
            parameters: $parameters,
            server: $this->createRequestServerPropertiesFromHeaders($headers)
        );

        return $this->client->getResponse();
    }
}
