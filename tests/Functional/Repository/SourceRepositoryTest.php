<?php

declare(strict_types=1);

namespace App\Tests\Functional\Repository;

use App\Entity\AbstractSource;
use App\Entity\GitSource;
use App\Repository\SourceRepository;
use App\Services\GitSourceFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SourceRepositoryTest extends WebTestCase
{
    private const USER_ID = '01FPSVJ7ZT85X73BW05EK9B3XG';

    private GitSourceFactory $gitSourceFactory;
    private EntityManagerInterface $entityManager;
    private SourceRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $gitSourceFactory = self::getContainer()->get(GitSourceFactory::class);
        \assert($gitSourceFactory instanceof GitSourceFactory);
        $this->gitSourceFactory = $gitSourceFactory;

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        \assert($entityManager instanceof EntityManagerInterface);
        $this->entityManager = $entityManager;

        $repository = self::getContainer()->get(SourceRepository::class);
        \assert($repository instanceof SourceRepository);
        $this->repository = $repository;

        $this->removeAllSources();
    }

    /**
     * @dataProvider persistAndRetrieveDataProvider
     *
     * @param callable(GitSourceFactory): AbstractSource $sourceCreator
     */
    public function testPersistAndRetrieveGitSource(callable $sourceCreator): void
    {
        $source = $sourceCreator($this->gitSourceFactory);

        $this->entityManager->persist($source);
        $this->entityManager->flush();

        $sourceId = $source->getId();

        $this->entityManager->detach($source);

        $retrievedSource = $this->repository->find($sourceId);
        self::assertInstanceOf($source::class, $retrievedSource);
        self::assertEquals($source, $retrievedSource);

        \assert(!is_null($retrievedSource));
        self::assertNotSame(spl_object_id($source), spl_object_id($retrievedSource));

        $this->removeAllSources();
    }

    /**
     * @return array<mixed>
     */
    public function persistAndRetrieveDataProvider(): array
    {
        return [
            GitSource::class => [
                'entity' => function (GitSourceFactory $gitSourceFactory) {
                    return $gitSourceFactory->create(
                        self::USER_ID,
                        'https://example.com/repository.git',
                        '/',
                        null
                    );
                },
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
