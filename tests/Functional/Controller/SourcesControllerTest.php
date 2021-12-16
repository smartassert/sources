<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Source;
use App\Repository\SourceRepository;
use App\Request\CreateSourceRequest;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Uid\Ulid;

class SourcesControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private SourceRepository $repository;
    private MockHandler $mockHandler;

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

        $this->removeAllSources();
    }

    public function testCreateUnauthorizedUser(): void
    {
        $this->mockHandler->append(
            new Response(401)
        );

        $this->client->request('POST', '/', [
            CreateSourceRequest::KEY_POST_HOST_URL => 'https://example.com/repository.git',
            CreateSourceRequest::KEY_POST_PATH => '/',
            CreateSourceRequest::KEY_POST_ACCESS_TOKEN => md5((string) rand()),
        ]);

        $response = $this->client->getResponse();

        self::assertSame(401, $response->getStatusCode());
    }

    /**
     * @dataProvider createDataProvider
     *
     * @param array<mixed> $requestParameters
     * @param array<mixed> $expected
     */
    public function testCreate(string $userId, array $requestParameters, array $expected): void
    {
        $this->mockHandler->append(
            new Response(200, [], $userId)
        );

        $this->client->request('POST', '/', $requestParameters);

        $response = $this->client->getResponse();

        self::assertSame(200, $response->getStatusCode());

        $repository = self::getContainer()->get(SourceRepository::class);
        \assert($repository instanceof SourceRepository);

        $source = $repository->findOneBy([
            'userId' => $userId,
            'hostUrl' => $requestParameters[CreateSourceRequest::KEY_POST_HOST_URL],
            'path' => $requestParameters[CreateSourceRequest::KEY_POST_PATH],
            'accessToken' => $requestParameters[CreateSourceRequest::KEY_POST_ACCESS_TOKEN] ?? null,
        ]);

        self::assertInstanceOf(Source::class, $source);

        \assert($source instanceof Source);
        $expected['id'] = $source->getId();

        self::assertEqualsCanonicalizing($expected, json_decode((string) $response->getContent(), true));
    }

    /**
     * @return array<mixed>
     */
    public function createDataProvider(): array
    {
        $userId = (string) new Ulid();
        $hostUrl = 'https://example.com/repository.git';
        $path = '/';
        $accessToken = md5((string) rand());

        return [
            'access token missing' => [
                'userId' => $userId,
                'requestParameters' => [
                    CreateSourceRequest::KEY_POST_HOST_URL => $hostUrl,
                    CreateSourceRequest::KEY_POST_PATH => $path
                ],
                'expected' => [
                    'user_id' => $userId,
                    'host_url' => $hostUrl,
                    'path' => $path,
                    'access_token' => null,
                ],
            ],
            'access token present' => [
                'userId' => $userId,
                'requestParameters' => [
                    CreateSourceRequest::KEY_POST_HOST_URL => $hostUrl,
                    CreateSourceRequest::KEY_POST_PATH => $path,
                    CreateSourceRequest::KEY_POST_ACCESS_TOKEN => $accessToken,
                ],
                'expected' => [
                    'user_id' => $userId,
                    'host_url' => $hostUrl,
                    'path' => $path,
                    'access_token' => $accessToken,
                ],
            ],
        ];
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
