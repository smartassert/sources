<?php

declare(strict_types=1);

namespace App\Tests\Functional\Repository;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Entity\SourceInterface;
use App\Repository\SourceRepository;
use App\Services\Source\Factory;
use App\Services\Source\Store;
use App\Tests\Services\Source\SourceRemover;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SourceRepositoryTest extends WebTestCase
{
    private const USER_ID = '01FPSVJ7ZT85X73BW05EK9B3XG';

    private Factory $factory;
    private SourceRepository $repository;
    private Store $store;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $factory = self::getContainer()->get(Factory::class);
        \assert($factory instanceof Factory);
        $this->factory = $factory;

        $repository = self::getContainer()->get(SourceRepository::class);
        \assert($repository instanceof SourceRepository);
        $this->repository = $repository;

        $store = self::getContainer()->get(Store::class);
        \assert($store instanceof Store);
        $this->store = $store;

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        \assert($entityManager instanceof EntityManagerInterface);
        $this->entityManager = $entityManager;

        $sourceRemover = self::getContainer()->get(SourceRemover::class);
        if ($sourceRemover instanceof SourceRemover) {
            $sourceRemover->removeAll();
        }
    }

    /**
     * @dataProvider persistAndRetrieveDataProvider
     *
     * @param callable(Factory): SourceInterface $sourceCreator
     */
    public function testPersistAndRetrieveSource(callable $sourceCreator): void
    {
        $source = $sourceCreator($this->factory);
        $sourceId = $source->getId();

        $this->store->add($source);
        $this->entityManager->detach($source);

        $retrievedSource = $this->repository->find($sourceId);
        self::assertInstanceOf($source::class, $retrievedSource);
        self::assertEquals($source, $retrievedSource);

        \assert(!is_null($retrievedSource));
        self::assertNotSame(spl_object_id($source), spl_object_id($retrievedSource));
    }

    /**
     * @return array<mixed>
     */
    public function persistAndRetrieveDataProvider(): array
    {
        return [
            GitSource::class => [
                'entity' => function (Factory $factory) {
                    return $factory->createGitSource(
                        self::USER_ID,
                        'https://example.com/repository.git',
                        '/',
                        null
                    );
                },
            ],
            FileSource::class => [
                'entity' => function (Factory $factory) {
                    return $factory->createFileSource(self::USER_ID, 'source label');
                },
            ],
            RunSource::class => [
                'entity' => function (Factory $factory) {
                    $parent = $factory->createFileSource(self::USER_ID, 'source label');

                    return $factory->createRunSource($parent);
                },
            ],
        ];
    }
}
