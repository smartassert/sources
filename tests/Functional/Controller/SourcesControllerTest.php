<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Controller\SourceController;
use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Entity\SourceInterface;
use App\Repository\FileSourceRepository;
use App\Repository\GitSourceRepository;
use App\Repository\SourceRepository;
use App\Request\FileSourceRequest;
use App\Request\GitSourceRequest;
use App\Services\Source\Store;
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
use Symfony\Component\Uid\Ulid;
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
                'uri' => SourceController::ROUTE_SOURCE . new Ulid(),
            ],
            'update source' => [
                'method' => 'PUT',
                'uri' => SourceController::ROUTE_SOURCE . new Ulid(),
            ],
            'delete source' => [
                'method' => 'DELETE',
                'uri' => SourceController::ROUTE_SOURCE . new Ulid(),
            ],
        ];
    }

    /**
     * @dataProvider requestSourceDataProvider
     */
    public function testRequestSourceNotFound(string $method): void
    {
        $sourceId = (string) new Ulid();

        $this->mockHandler->append(
            new Response(200, [], (string) new Ulid())
        );

        $response = $this->makeAuthorizedSourceRequest($method, $sourceId);
        self::assertSame(404, $response->getStatusCode());
    }

    /**
     * @dataProvider requestSourceDataProvider
     */
    public function testRequestInvalidSourceUser(string $method): void
    {
        $sourceId = (string) new Ulid();

        $sourceUserId = (string) new Ulid();
        $requestUserId = (string) new Ulid();
        $label = 'source label';

        $source = new FileSource($sourceId, $sourceUserId, $label);
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
     * @param array<mixed> $requestParameters
     * @param array<mixed> $expected
     */
    public function testCreateGitSourceSuccess(string $userId, array $requestParameters, array $expected): void
    {
        $this->mockHandler->append(
            new Response(200, [], $userId)
        );

        $this->client->request(
            'POST',
            SourceController::ROUTE_GIT_SOURCE_CREATE,
            $requestParameters,
            [],
            $this->createRequestServerPropertiesFromHeaders(
                $this->createAuthorizationHeader()
            ),
        );

        $response = $this->client->getResponse();

        self::assertSame(200, $response->getStatusCode());
        $this->assertAuthorizationRequestIsMade();

        $repository = self::getContainer()->get(GitSourceRepository::class);
        \assert($repository instanceof GitSourceRepository);

        $source = $repository->findOneBy([
            'userId' => $userId,
            'hostUrl' => $requestParameters[GitSourceRequest::KEY_POST_HOST_URL],
            'path' => $requestParameters[GitSourceRequest::KEY_POST_PATH],
            'accessToken' => $requestParameters[GitSourceRequest::KEY_POST_ACCESS_TOKEN] ?? null,
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
        $userId = (string) new Ulid();
        $hostUrl = 'https://example.com/repository.git';
        $path = '/';
        $accessToken = md5((string) rand());

        return [
            'access token missing' => [
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
                    'access_token' => null,
                ],
            ],
            'access token present' => [
                'userId' => $userId,
                'requestParameters' => [
                    GitSourceRequest::KEY_POST_HOST_URL => $hostUrl,
                    GitSourceRequest::KEY_POST_PATH => $path,
                    GitSourceRequest::KEY_POST_ACCESS_TOKEN => $accessToken,
                ],
                'expected' => [
                    'user_id' => $userId,
                    'type' => SourceInterface::TYPE_GIT,
                    'host_url' => $hostUrl,
                    'path' => $path,
                    'access_token' => $accessToken,
                ],
            ],
        ];
    }

    /**
     * @dataProvider createFileSourceDataProvider
     *
     * @param array<mixed> $requestParameters
     * @param array<mixed> $expected
     */
    public function testCreateFileSourceSuccess(string $userId, array $requestParameters, array $expected): void
    {
        $this->mockHandler->append(
            new Response(200, [], $userId)
        );

        $this->client->request(
            'POST',
            SourceController::ROUTE_FILE_SOURCE_CREATE,
            $requestParameters,
            [],
            $this->createRequestServerPropertiesFromHeaders(
                $this->createAuthorizationHeader()
            ),
        );

        $response = $this->client->getResponse();

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
        $userId = (string) new Ulid();
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
     * @param callable(Store): SourceInterface $sourceCreator
     * @param array<mixed>                     $expectedResponseData
     */
    public function testGetSuccess(callable $sourceCreator, string $userId, array $expectedResponseData): void
    {
        $source = $sourceCreator($this->store);

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
        $gitSourceId = (string) new Ulid();
        $fileSourceId = (string) new Ulid();
        $runSourceId = (string) new Ulid();
        $userId = (string) new Ulid();
        $hostUrl = 'https://example.com/repository.git';
        $path = '/';
        $accessToken = md5((string) rand());
        $label = 'file source label';

        return [
            SourceInterface::TYPE_GIT => [
                'sourceCreator' => function (
                    Store $store
                ) use (
                    $gitSourceId,
                    $userId,
                    $hostUrl,
                    $path,
                    $accessToken
                ) {
                    $source = new GitSource($gitSourceId, $userId, $hostUrl, $path, $accessToken);
                    $store->add($source);

                    return $source;
                },
                'userId' => $userId,
                'expectedResponseData' => [
                    'id' => $gitSourceId,
                    'user_id' => $userId,
                    'type' => SourceInterface::TYPE_GIT,
                    'host_url' => $hostUrl,
                    'path' => $path,
                    'access_token' => $accessToken,
                ],
            ],
            SourceInterface::TYPE_FILE => [
                'sourceCreator' => function (Store $store) use ($fileSourceId, $userId, $label) {
                    $source = new FileSource($fileSourceId, $userId, $label);
                    $store->add($source);

                    return $source;
                },
                'userId' => $userId,
                'expectedResponseData' => [
                    'id' => $fileSourceId,
                    'user_id' => $userId,
                    'type' => SourceInterface::TYPE_FILE,
                    'label' => $label,
                ],
            ],
            SourceInterface::TYPE_RUN => [
                'sourceCreator' => function (
                    Store $store
                ) use (
                    $fileSourceId,
                    $runSourceId,
                    $userId,
                    $label
                ) {
                    $parent = new FileSource($fileSourceId, $userId, $label);
                    $store->add($parent);

                    $source = new RunSource($runSourceId, $parent);
                    $store->add($source);

                    return $source;
                },
                'userId' => $userId,
                'expectedResponseData' => [
                    'id' => $runSourceId,
                    'user_id' => $userId,
                    'type' => SourceInterface::TYPE_RUN,
                    'parent' => $fileSourceId,
                    'parameters' => [],
                ],
            ],
        ];
    }

    /**
     * @dataProvider updateSuccessDataProvider
     *
     * @param callable(Store): SourceInterface $sourceCreator
     * @param array<string, string>            $requestData
     * @param array<mixed>                     $expectedResponseData
     */
    public function testUpdateSuccess(
        callable $sourceCreator,
        string $userId,
        array $requestData,
        array $expectedResponseData
    ): void {
        $source = $sourceCreator($this->store);

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
        $gitSourceId = (string) new Ulid();
        $userId = (string) new Ulid();
        $hostUrl = 'https://example.com/repository.git';
        $path = '/';
        $accessToken = md5((string) rand());
        $newHostUrl = 'https://new.example.com/repository.git';
        $newPath = '/new';

        return [
            SourceInterface::TYPE_GIT => [
                'sourceCreator' => function (
                    Store $store
                ) use (
                    $gitSourceId,
                    $userId,
                    $hostUrl,
                    $path,
                    $accessToken
                ) {
                    $source = new GitSource($gitSourceId, $userId, $hostUrl, $path, $accessToken);
                    $store->add($source);

                    return $source;
                },
                'userId' => $userId,
                'requestData' => [
                    GitSourceRequest::KEY_POST_HOST_URL => $newHostUrl,
                    GitSourceRequest::KEY_POST_PATH => $newPath,
                    GitSourceRequest::KEY_POST_ACCESS_TOKEN => null,
                ],
                'expectedResponseData' => [
                    'id' => $gitSourceId,
                    'user_id' => $userId,
                    'type' => SourceInterface::TYPE_GIT,
                    'host_url' => $newHostUrl,
                    'path' => $newPath,
                    'access_token' => null,
                ],
            ],
        ];
    }

    /**
     * @dataProvider deleteSuccessDataProvider
     *
     * @param callable(Store): SourceInterface $sourceCreator
     */
    public function testDeleteSuccess(callable $sourceCreator, string $userId, int $expectedRepositoryCount): void
    {
        $source = $sourceCreator($this->store);
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
        $userId = (string) new Ulid();

        return [
            SourceInterface::TYPE_FILE => [
                'sourceCreator' => function (Store $store) use ($userId) {
                    $source = new FileSource((string) new Ulid(), $userId, 'label');
                    $store->add($source);

                    return $source;
                },
                'userId' => $userId,
                'expectedRepositoryCount' => 0,
            ],
            SourceInterface::TYPE_GIT => [
                'sourceCreator' => function (Store $store) use ($userId) {
                    $source = new GitSource(
                        (string) new Ulid(),
                        $userId,
                        'https://example.com/repository.git'
                    );
                    $store->add($source);

                    return $source;
                },
                'userId' => $userId,
                'expectedRepositoryCount' => 0,
            ],
            SourceInterface::TYPE_RUN => [
                'sourceCreator' => function (Store $store) use ($userId) {
                    $parent = new FileSource((string) new Ulid(), $userId, 'label');
                    $store->add($parent);

                    $source = new RunSource((string) new Ulid(), $parent);
                    $store->add($source);

                    return $source;
                },
                'userId' => $userId,
                'expectedRepositoryCount' => 1,
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
        return $this->makeSourceRequest($method, $sourceId, $this->createAuthorizationHeader(), $parameters);
    }

    /**
     * @param array<string, string> $headers
     * @param array<string, string> $parameters
     */
    private function makeSourceRequest(
        string $method,
        string $sourceId,
        array $headers = [],
        array $parameters = []
    ): SymfonyResponse {
        $this->client->request(
            method: $method,
            uri: SourceController::ROUTE_SOURCE . $sourceId,
            parameters: $parameters,
            server: $this->createRequestServerPropertiesFromHeaders($headers)
        );

        return $this->client->getResponse();
    }
}
