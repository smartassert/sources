<?php

declare(strict_types=1);

namespace App\Tests\Functional\Repository;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Entity\SourceInterface;
use App\Repository\SourceRepository;
use App\Services\SourceFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SourceRepositoryTest extends WebTestCase
{
    private const USER_ID = '01FPSVJ7ZT85X73BW05EK9B3XG';

    private SourceFactory $sourceFactory;
    private EntityManagerInterface $entityManager;
    private SourceRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $gitSourceFactory = self::getContainer()->get(SourceFactory::class);
        \assert($gitSourceFactory instanceof SourceFactory);
        $this->sourceFactory = $gitSourceFactory;

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
     * @param callable(SourceFactory): SourceInterface $sourceCreator
     */
    public function testPersistAndRetrieveSource(callable $sourceCreator): void
    {
        $source = $sourceCreator($this->sourceFactory);

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
                'entity' => function (SourceFactory $factory) {
                    return $factory->createGitSource(
                        self::USER_ID,
                        'https://example.com/repository.git',
                        '/',
                        null
                    );
                },
            ],
            FileSource::class => [
                'entity' => function (SourceFactory $factory) {
                    return $factory->createFileSource(self::USER_ID, 'source label');
                },
            ],
            RunSource::class => [
                'entity' => function (SourceFactory $factory) {
                    $parent = $factory->createFileSource(self::USER_ID, 'source label');

                    return $factory->createRunSource(self::USER_ID, $parent);
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
