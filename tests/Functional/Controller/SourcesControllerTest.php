<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Controller\SourceController;
use App\Entity\AbstractSource;
use App\Entity\SourceTypeInterface;
use App\Repository\GitSourceRepository;
use App\Repository\SourceRepository;
use App\Request\CreateGitSourceRequest;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use SmartAssert\UsersClient\Routes;
use SmartAssert\UsersSecurityBundle\Security\AuthorizationProperties;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Uid\Ulid;
use webignition\HttpHistoryContainer\Container as HttpHistoryContainer;

class SourcesControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private SourceRepository $repository;
    private MockHandler $mockHandler;
    private HttpHistoryContainer $httpHistoryContainer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        \assert($entityManager instanceof EntityManagerInterface);
        $this->entityManager = $entityManager;

        $repository = self::getContainer()->get(SourceRepository::class);
        \assert($repository instanceof SourceRepository);
        $this->repository = $repository;

        $mockHandler = self::getContainer()->get(MockHandler::class);
        \assert($mockHandler instanceof MockHandler);
        $this->mockHandler = $mockHandler;

        $httpHistoryContainer = self::getContainer()->get(HttpHistoryContainer::class);
        \assert($httpHistoryContainer instanceof HttpHistoryContainer);
        $this->httpHistoryContainer = $httpHistoryContainer;

        $handlerStack = self::getContainer()->get(HandlerStack::class);
        \assert($handlerStack instanceof HandlerStack);
        $handlerStack->push(Middleware::history($this->httpHistoryContainer), 'history');

        $this->removeAllSources();
    }

    public function testCreateUnauthorizedUser(): void
    {
        $this->mockHandler->append(
            new Response(401)
        );

        $token = 'invalid-token';
        $authHeaderName = AuthorizationProperties::DEFAULT_HEADER_NAME;
        $authHeaderValue = AuthorizationProperties::DEFAULT_VALUE_PREFIX . $token;

        $this->client->request(
            'POST',
            SourceController::ROUTE_GIT_SOURCE_CREATE,
            [
                CreateGitSourceRequest::KEY_POST_HOST_URL => 'https://example.com/repository.git',
                CreateGitSourceRequest::KEY_POST_PATH => '/',
            ],
            [],
            [
                'HTTP_' . $authHeaderName => $authHeaderValue,
            ]
        );

        $response = $this->client->getResponse();

        self::assertSame(401, $response->getStatusCode());
        $this->assertAuthorizationRequestIsMade($token);
    }

    /**
     * @dataProvider createGitSourceDataProvider
     *
     * @param array<mixed> $requestParameters
     * @param array<mixed> $expected
     */
    public function testCreateGitSourceSuccess(string $userId, array $requestParameters, array $expected): void
    {
        $token = 'valid-token';
        $authHeaderName = AuthorizationProperties::DEFAULT_HEADER_NAME;
        $authHeaderValue = AuthorizationProperties::DEFAULT_VALUE_PREFIX . $token;

        $this->mockHandler->append(
            new Response(200, [], $userId)
        );

        $this->client->request(
            'POST',
            SourceController::ROUTE_GIT_SOURCE_CREATE,
            $requestParameters,
            [],
            [
                'HTTP_' . $authHeaderName => $authHeaderValue,
            ]
        );

        $response = $this->client->getResponse();

        self::assertSame(200, $response->getStatusCode());
        $this->assertAuthorizationRequestIsMade($token);

        $repository = self::getContainer()->get(GitSourceRepository::class);
        \assert($repository instanceof GitSourceRepository);

        $source = $repository->findOneBy([
            'userId' => $userId,
            'hostUrl' => $requestParameters[CreateGitSourceRequest::KEY_POST_HOST_URL],
            'path' => $requestParameters[CreateGitSourceRequest::KEY_POST_PATH],
            'accessToken' => $requestParameters[CreateGitSourceRequest::KEY_POST_ACCESS_TOKEN] ?? null,
        ]);

        self::assertInstanceOf(AbstractSource::class, $source);

        \assert($source instanceof AbstractSource);
        $expected['id'] = $source->getId();

        self::assertEqualsCanonicalizing($expected, json_decode((string) $response->getContent(), true));
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
                    CreateGitSourceRequest::KEY_POST_HOST_URL => $hostUrl,
                    CreateGitSourceRequest::KEY_POST_PATH => $path
                ],
                'expected' => [
                    'user_id' => $userId,
                    'type' => SourceTypeInterface::TYPE_GIT,
                    'host_url' => $hostUrl,
                    'path' => $path,
                    'access_token' => null,
                ],
            ],
            'access token present' => [
                'userId' => $userId,
                'requestParameters' => [
                    CreateGitSourceRequest::KEY_POST_HOST_URL => $hostUrl,
                    CreateGitSourceRequest::KEY_POST_PATH => $path,
                    CreateGitSourceRequest::KEY_POST_ACCESS_TOKEN => $accessToken,
                ],
                'expected' => [
                    'user_id' => $userId,
                    'type' => SourceTypeInterface::TYPE_GIT,
                    'host_url' => $hostUrl,
                    'path' => $path,
                    'access_token' => $accessToken,
                ],
            ],
        ];
    }

    private function assertAuthorizationRequestIsMade(string $token): void
    {
        $request = $this->httpHistoryContainer->getTransactions()->getRequests()->getLast();
        \assert($request instanceof RequestInterface);

        $usersServiceBaseUrl = self::getContainer()->getParameter('users_security_bundle_base_url');
        \assert(is_string($usersServiceBaseUrl));

        $expectedUrl = $usersServiceBaseUrl . Routes::DEFAULT_VERIFY_API_TOKEN_PATH;

        self::assertSame($expectedUrl, (string) $request->getUri());

        $authorizationHeader = $request->getHeaderLine(AuthorizationProperties::DEFAULT_HEADER_NAME);

        $expectedAuthorizationHeader = AuthorizationProperties::DEFAULT_VALUE_PREFIX . $token;

        self::assertSame($expectedAuthorizationHeader, $authorizationHeader);
    }

    private function removeAllSources(): void
    {
        $sources = $this->repository->findAll();

        foreach ($sources as $source) {
            $this->entityManager->remove($source);
        }

        $this->entityManager->flush();
    }
}
