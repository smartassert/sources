<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Source;
use App\Repository\SourceRepository;
use App\Request\CreateSourceRequest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Uid\Ulid;

class SourcesControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private SourceRepository $repository;

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

        $this->removeAllSources();
    }

    /**
     * @dataProvider createDataProvider
     *
     * @param array<mixed> $requestParameters
     * @param array<mixed> $expected
     */
    public function testCreate(string $userId, array $requestParameters, array $expected): void
    {
        $this->client->request('POST', '/' . $userId, $requestParameters);

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
