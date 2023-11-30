<?php

declare(strict_types=1);

namespace App\Tests\Functional\Application;

use App\Entity\FileSource;
use App\Repository\SourceRepository;
use App\Services\SourceRepository\Reader\FileSourceDirectoryLister;
use App\Tests\Services\ApplicationClient\Client;
use App\Tests\Services\ApplicationClient\ClientFactory;
use League\Flysystem\FilesystemException;
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

    protected function setUp(): void
    {
        parent::setUp();

        $kernelBrowser = self::createClient();

        $client = self::getContainer()->get(SymfonyClient::class);
        \assert($client instanceof SymfonyClient);
        $client->setKernelBrowser($kernelBrowser);

        $factory = self::getContainer()->get(ClientFactory::class);
        \assert($factory instanceof ClientFactory);

        $this->applicationClient = $factory->create($client);
    }

    public function testListFileSourceFilenamesHandlesThrownFilesystemException(): void
    {
        $userId = md5((string) rand());

        $this->mockAuthenticator($userId);

        $source = \Mockery::mock(FileSource::class);
        $source
            ->shouldReceive('getUserId')
            ->andReturn($userId)
        ;

        $sourceRepository = \Mockery::mock(SourceRepository::class);
        $sourceRepository
            ->shouldReceive('find')
            ->andReturn($source)
        ;

        self::getContainer()->set(SourceRepository::class, $sourceRepository);

        $fileSourceDirectoryLister = \Mockery::mock(FileSourceDirectoryLister::class);

        $exception = (new class () extends \Exception implements FilesystemException {
            public function __construct()
            {
                parent::__construct('message', 123);
            }
        });

        $fileSourceDirectoryLister
            ->shouldReceive('list')
            ->andThrow($exception)
        ;

        self::getContainer()->set(FileSourceDirectoryLister::class, $fileSourceDirectoryLister);

        $sourceId = (string) new Ulid();

        $response = $this->applicationClient->makeGetFileSourceFilenamesRequest(
            'api token',
            $sourceId
        );

        self::assertSame(500, $response->getStatusCode());
        self::assertSame('application/json', $response->getHeaderLine('content-type'));

        $expectedData = [
            'error' => [
                'payload' => [
                    'file' => '',
                    'message' => 'message',
                ],
                'type' => 'source_unknown_exception',
            ],
        ];

        self::assertJsonStringEqualsJsonString(
            (string) json_encode($expectedData),
            $response->getBody()->getContents()
        );
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
}
