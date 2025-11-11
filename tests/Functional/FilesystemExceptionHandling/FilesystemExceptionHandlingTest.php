<?php

declare(strict_types=1);

namespace App\Tests\Functional\FilesystemExceptionHandling;

use App\Entity\EntityIdentifier;
use App\Entity\FileSource;
use App\Entity\IdentifiedEntityInterface as IdentifiedEntity;
use App\Entity\SerializedSuite;
use App\Entity\SourceInterface;
use App\Enum\EntityType;
use App\Repository\SerializedSuiteRepository;
use App\Repository\SourceRepository;
use App\Services\SourceRepository\Reader\DirectoryListingFactoryInterface;
use App\Services\SourceRepository\Reader\FileSourceDirectoryLister;
use App\Tests\Services\ApplicationClient\Client;
use App\Tests\Services\StringFactory;
use League\Flysystem\FilesystemException as FsException;
use League\Flysystem\FilesystemOperationFailed as FsOpFailed;
use League\Flysystem\FilesystemOperator;
use Mockery\MockInterface as MockI;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\ResponseInterface;
use SmartAssert\SymfonyTestClient\SymfonyClient;
use SmartAssert\UsersSecurityBundle\Security\Authenticator;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Uid\Ulid;

class FilesystemExceptionHandlingTest extends WebTestCase
{
    private Client $applicationClient;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $kernelBrowser = self::createClient();

        $client = self::getContainer()->get(SymfonyClient::class);
        \assert($client instanceof SymfonyClient);
        $client->setKernelBrowser($kernelBrowser);

        $this->applicationClient = new Client($client);
    }

    /**
     * @param callable(IdentifiedEntity): array<mixed> $expectedResponseDataCreator
     */
    #[DataProvider('exceptionHandlerDataProvider')]
    public function testListFileSourceFilenamesHandlesThrownFilesystemException(
        \Exception&FsException $exception,
        callable $expectedResponseDataCreator,
    ): void {
        $userId = StringFactory::createRandom();
        $sourceId = (string) new Ulid();

        $this->mockAuthenticator($userId);

        $source = $this->createSource($userId, $sourceId);
        $this->createSourceRepository($source);

        $fileSourceDirectoryLister = \Mockery::mock(DirectoryListingFactoryInterface::class);
        $fileSourceDirectoryLister
            ->shouldReceive('list')
            ->andThrow($exception)
        ;

        self::getContainer()->set(FileSourceDirectoryLister::class, $fileSourceDirectoryLister);

        $response = $this->applicationClient->makeGetFileSourceFilenamesRequest('api token', (string) new Ulid());

        $expectedResponseData = $expectedResponseDataCreator($source);

        $this->assertResponse($response, $expectedResponseData);
    }

    /**
     * @param callable(IdentifiedEntity): array<mixed> $expectedResponseDataCreator
     */
    #[DataProvider('exceptionHandlerDataProvider')]
    public function testAddFileSourceFileHandlesThrownFilesystemException(
        \Exception&FsException $exception,
        callable $expectedResponseDataCreator,
    ): void {
        $userId = StringFactory::createRandom();
        $sourceId = (string) new Ulid();

        $this->mockAuthenticator($userId);

        $source = $this->createSource($userId, $sourceId);
        $this->createSourceRepository($source);

        $this->mockFileSourceStorageCall('fileExists', $exception);

        $response = $this->applicationClient->makeAddFileRequest(
            'api token',
            $sourceId,
            StringFactory::createRandom() . '.yaml',
            StringFactory::createRandom()
        );

        $expectedResponseData = $expectedResponseDataCreator($source);

        $this->assertResponse($response, $expectedResponseData);
    }

    /**
     * @param callable(IdentifiedEntity): array<mixed> $expectedResponseDataCreator
     */
    #[DataProvider('exceptionHandlerDataProvider')]
    public function testUpdateFileSourceFileHandlesThrownFilesystemException(
        \Exception&FsException $exception,
        callable $expectedResponseDataCreator,
    ): void {
        $userId = StringFactory::createRandom();
        $sourceId = (string) new Ulid();

        $this->mockAuthenticator($userId);

        $source = $this->createSource($userId, $sourceId);
        $this->createSourceRepository($source);

        $this->mockFileSourceStorageCall('write', $exception);

        $response = $this->applicationClient->makeUpdateFileRequest(
            'api token',
            $sourceId,
            StringFactory::createRandom() . '.yaml',
            StringFactory::createRandom()
        );

        $expectedResponseData = $expectedResponseDataCreator($source);

        $this->assertResponse($response, $expectedResponseData);
    }

    /**
     * @param callable(IdentifiedEntity): array<mixed> $expectedResponseDataCreator
     */
    #[DataProvider('exceptionHandlerDataProvider')]
    public function testReadFileSourceFileHandlesThrownFilesystemException(
        \Exception&FsException $exception,
        callable $expectedResponseDataCreator,
    ): void {
        $userId = StringFactory::createRandom();
        $sourceId = (string) new Ulid();

        $this->mockAuthenticator($userId);

        $source = $this->createSource($userId, $sourceId);

        $this->createSourceRepository($source);
        $this->mockFileSourceStorageCall('fileExists', $exception);

        $response = $this->applicationClient->makeReadFileRequest(
            'api token',
            $sourceId,
            StringFactory::createRandom() . '.yaml'
        );

        $expectedResponseData = $expectedResponseDataCreator($source);

        $this->assertResponse($response, $expectedResponseData);
    }

    /**
     * @param callable(IdentifiedEntity): array<mixed> $expectedResponseDataCreator
     */
    #[DataProvider('exceptionHandlerDataProvider')]
    public function testDeleteFileSourceFileHandlesThrownFilesystemException(
        \Exception&FsException $exception,
        callable $expectedResponseDataCreator,
    ): void {
        $userId = StringFactory::createRandom();
        $sourceId = (string) new Ulid();

        $this->mockAuthenticator($userId);

        $source = $this->createSource($userId, $sourceId);
        $this->createSourceRepository($source);

        $this->mockFileSourceStorageCall('delete', $exception);

        $response = $this->applicationClient->makeRemoveFileRequest(
            'api token',
            $sourceId,
            StringFactory::createRandom() . '.yaml'
        );

        $expectedResponseData = $expectedResponseDataCreator($source);

        $this->assertResponse($response, $expectedResponseData);
    }

    /**
     * @param callable(IdentifiedEntity): array<mixed> $expectedResponseDataCreator
     */
    #[DataProvider('exceptionHandlerDataProvider')]
    public function testReadSerializedSuiteHandlesThrownFilesystemException(
        \Exception&FsException $exception,
        callable $expectedResponseDataCreator,
    ): void {
        $userId = StringFactory::createRandom();
        $serializedSuiteId = (string) new Ulid();

        $this->mockAuthenticator($userId);

        $serializedSuite = \Mockery::mock(SerializedSuite::class);

        $serializedSuite
            ->shouldReceive('getUserId')
            ->andReturn($userId)
        ;

        $serializedSuite
            ->shouldReceive('getDirectoryPath')
            ->andReturn($userId . '/' . $serializedSuiteId)
        ;

        $serializedSuite
            ->shouldReceive('getIdentifier')
            ->andReturn(new EntityIdentifier($serializedSuiteId, EntityType::SERIALIZED_SUITE->value))
        ;

        $serializedSuiteRepository = \Mockery::mock(SerializedSuiteRepository::class);
        $serializedSuiteRepository
            ->shouldReceive('find')
            ->andReturn($serializedSuite)
        ;

        self::getContainer()->set(SerializedSuiteRepository::class, $serializedSuiteRepository);

        $filesystemOperator = \Mockery::mock(FilesystemOperator::class);
        $filesystemOperator
            ->shouldReceive('fileExists')
            ->andThrow($exception)
        ;

        self::getContainer()->set('serialized_suite.storage', $filesystemOperator);

        $response = $this->applicationClient->makeReadSerializedSuiteRequest(
            'api token',
            $serializedSuiteId,
        );

        $expectedResponseData = $expectedResponseDataCreator($serializedSuite);

        $this->assertResponse($response, $expectedResponseData);
    }

    /**
     * @param callable(IdentifiedEntity): array<mixed> $expectedResponseDataCreator
     */
    #[DataProvider('exceptionHandlerDataProvider')]
    public function testDeleteSourceHandlesThrownFilesystemException(
        \Exception&FsException $exception,
        callable $expectedResponseDataCreator,
    ): void {
        $userId = StringFactory::createRandom();
        $sourceId = (string) new Ulid();

        $this->mockAuthenticator($userId);

        $source = $this->createSource($userId, $sourceId);
        $source
            ->shouldReceive('getDeletedAt')
            ->andReturn(null)
        ;

        $sourceRepository = $this->createSourceRepository($source);
        $sourceRepository->shouldReceive('delete');

        $this->mockFileSourceStorageCall('deleteDirectory', $exception);

        $response = $this->applicationClient->makeDeleteSourceRequest('api token', $sourceId);

        $expectedResponseData = $expectedResponseDataCreator($source);

        $this->assertResponse($response, $expectedResponseData);
    }

    /**
     * @return array<mixed>
     */
    public static function exceptionHandlerDataProvider(): array
    {
        $message = StringFactory::createRandom();
        $code = rand();
        $location = StringFactory::createRandom();

        return [
            'generic filesystem error' => [
                'exception' => new class ($message, $code) extends \Exception implements FsException {
                    public function __construct(string $message, int $code)
                    {
                        parent::__construct($message, $code);
                    }
                },
                'expectedResponseDataCreator' => function (IdentifiedEntity $entity) {
                    return [
                        'class' => 'storage',
                        'type' => null,
                        'location' => null,
                        'object_type' => 'entity',
                        'context' => $entity->getIdentifier()->serialize(),
                    ];
                },
            ],
            'read failed, no known location' => [
                'exception' => new class ($message, $code) extends \Exception implements FsOpFailed {
                    public function __construct(string $message, int $code)
                    {
                        parent::__construct($message, $code);
                    }

                    public function operation(): string
                    {
                        return 'read';
                    }
                },
                'expectedResponseDataCreator' => function (IdentifiedEntity $entity) {
                    return [
                        'class' => 'storage',
                        'location' => null,
                        'object_type' => 'entity',
                        'context' => $entity->getIdentifier()->serialize(),
                        'type' => 'read',
                    ];
                },
            ],
            'write failed, no known location' => [
                'exception' => new class ($message, $code) extends \Exception implements FsOpFailed {
                    public function __construct(string $message, int $code)
                    {
                        parent::__construct($message, $code);
                    }

                    public function operation(): string
                    {
                        return 'write';
                    }
                },
                'expectedResponseDataCreator' => function (IdentifiedEntity $entity) {
                    return [
                        'class' => 'storage',
                        'location' => null,
                        'object_type' => 'entity',
                        'context' => $entity->getIdentifier()->serialize(),
                        'type' => 'write',
                    ];
                },
            ],
            'write failed, known location' => [
                'exception' => new class ($location, $message, $code) extends \Exception implements FsOpFailed {
                    public function __construct(private readonly string $location, string $message, int $code)
                    {
                        parent::__construct($message, $code);
                    }

                    public function operation(): string
                    {
                        return 'write';
                    }

                    public function location(): string
                    {
                        return $this->location;
                    }
                },
                'expectedResponseDataCreator' => function (IdentifiedEntity $entity) use ($location) {
                    return [
                        'class' => 'storage',
                        'location' => $location,
                        'object_type' => 'entity',
                        'context' => $entity->getIdentifier()->serialize(),
                        'type' => 'write',
                    ];
                },
            ],
        ];
    }

    private function mockAuthenticator(string $userId): void
    {
        $user = \Mockery::mock(UserInterface::class);
        $user
            ->shouldReceive('getUserIdentifier')
            ->andReturn($userId)
        ;

        $token = $this->createToken($user);

        $authenticator = $this->createAuthenticator($token);

        self::getContainer()->set(Authenticator::class, $authenticator);
    }

    private function createToken(UserInterface $user): TokenInterface
    {
        $token = \Mockery::mock(TokenInterface::class);
        $token->shouldReceive('eraseCredentials');

        $token
            ->shouldReceive('getUser')
            ->andReturn($user)
        ;

        $token
            ->shouldReceive('getRoleNames')
            ->andReturn(['ROLE_USER'])
        ;

        return $token;
    }

    private function createAuthenticator(TokenInterface $token): Authenticator
    {
        $user = $token->getUser();
        \assert($user instanceof UserInterface);

        $authenticator = \Mockery::mock(Authenticator::class);

        $authenticator
            ->shouldReceive('supports')
            ->andReturn(true)
        ;

        $authenticator
            ->shouldReceive('authenticate')
            ->andReturn(new SelfValidatingPassport(new UserBadge($user->getUserIdentifier())))
        ;

        $authenticator
            ->shouldReceive('onAuthenticationSuccess')
            ->andReturnNull()
        ;

        $authenticator
            ->shouldReceive('createToken')
            ->andReturn($token)
        ;

        return $authenticator;
    }

    private function mockFileSourceStorageCall(string $method, \Exception $exception): void
    {
        $filesystemOperator = \Mockery::mock(FilesystemOperator::class);
        $filesystemOperator
            ->shouldReceive($method)
            ->andThrow($exception)
        ;

        self::getContainer()->set('file_source.storage', $filesystemOperator);
    }

    /**
     * @param array<mixed> $expectedResponseData
     */
    private function assertResponse(ResponseInterface $response, array $expectedResponseData): void
    {
        self::assertSame(500, $response->getStatusCode());
        self::assertSame('application/json', $response->getHeaderLine('content-type'));

        self::assertJsonStringEqualsJsonString(
            (string) json_encode($expectedResponseData),
            $response->getBody()->getContents()
        );
    }

    /**
     * @param non-empty-string $sourceId
     */
    private function createSource(string $userId, string $sourceId): IdentifiedEntity&MockI&SourceInterface
    {
        $source = \Mockery::mock(FileSource::class);
        $source
            ->shouldReceive('getUserId')
            ->andReturn($userId)
        ;

        $source
            ->shouldReceive('getIdentifier')
            ->andReturn(new EntityIdentifier($sourceId, EntityType::FILE_SOURCE->value))
        ;

        $source
            ->shouldReceive('getDirectoryPath')
            ->andReturn($userId . '/' . $sourceId)
        ;

        $source
            ->shouldReceive('getId')
            ->andReturn($sourceId)
        ;

        $source
            ->shouldReceive('getDeletedAt')
            ->andReturnNull()
        ;

        return $source;
    }

    private function createSourceRepository(SourceInterface $source): MockI&SourceRepository
    {
        $sourceRepository = \Mockery::mock(SourceRepository::class);
        $sourceRepository
            ->shouldReceive('find')
            ->andReturn($source)
        ;

        self::getContainer()->set(SourceRepository::class, $sourceRepository);

        return $sourceRepository;
    }
}
