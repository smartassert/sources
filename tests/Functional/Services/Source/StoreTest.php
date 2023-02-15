<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services\Source;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Entity\SourceInterface;
use App\Repository\SourceRepository;
use App\Services\Source\Store;
use App\Tests\Model\UserId;
use App\Tests\Services\EntityRemover;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class StoreTest extends WebTestCase
{
    private Store $store;
    private SourceRepository $repository;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $store = self::getContainer()->get(Store::class);
        \assert($store instanceof Store);
        $this->store = $store;

        $repository = self::getContainer()->get(SourceRepository::class);
        \assert($repository instanceof SourceRepository);
        $this->repository = $repository;

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        \assert($entityManager instanceof EntityManagerInterface);
        $this->entityManager = $entityManager;

        $entityRemover = self::getContainer()->get(EntityRemover::class);
        if ($entityRemover instanceof EntityRemover) {
            $entityRemover->removeAll();
        }
    }

    /**
     * @dataProvider deleteDataProvider
     *
     * @param callable(Store): SourceInterface $sourceCreator
     */
    public function testDeleteNonEncapsulatingSource(callable $sourceCreator): void
    {
        $source = $sourceCreator($this->store);
        $sourceId = $source->getId();

        self::assertCount(1, $this->repository->findAll());

        $this->store->delete($source);
        $this->entityManager->detach($source);

        self::assertCount(1, $this->repository->findAll());

        $foundSource = $this->repository->find($sourceId);
        self::assertInstanceOf(SourceInterface::class, $foundSource);
        self::assertNotNull($foundSource->getDeletedAt());
    }

    /**
     * @return array<mixed>
     */
    public function deleteDataProvider(): array
    {
        return [
            FileSource::class => [
                'sourceCreator' => function (Store $store): SourceInterface {
                    $source = new FileSource(UserId::create(), 'label');
                    $store->add($source);

                    return $source;
                },
            ],
            GitSource::class => [
                'sourceCreator' => function (Store $store): SourceInterface {
                    $source = new GitSource(UserId::create(), 'label', 'https://example.com/repository.git');
                    $store->add($source);

                    return $source;
                },
            ],
            RunSource::class => [
                'sourceCreator' => function (Store $store): SourceInterface {
                    $parent = new FileSource(UserId::create(), 'label');
                    $source = new RunSource($parent);
                    $source->unsetParent();

                    $store->add($source);

                    return $source;
                },
            ],
        ];
    }

    public function testDeleteRunSource(): void
    {
        $parent = new FileSource(UserId::create(), 'label');
        $parentId = $parent->getId();
        $this->store->add($parent);

        $source = new RunSource($parent);
        $sourceId = $source->getId();
        $this->store->add($source);

        self::assertCount(2, $this->repository->findAll());

        $this->store->delete($source);
        $this->entityManager->clear();

        self::assertCount(2, $this->repository->findAll());

        $retrievedParent = $this->repository->find($parentId);
        self::assertInstanceOf(SourceInterface::class, $retrievedParent);
        self::assertNull($retrievedParent->getDeletedAt());

        $retrievedSource = $this->repository->find($sourceId);
        self::assertInstanceOf(SourceInterface::class, $retrievedSource);
        self::assertNotNull($retrievedSource->getDeletedAt());
    }

    public function testAddRunSource(): void
    {
        $parent = new FileSource(UserId::create(), 'label');
        $runSource1 = new RunSource($parent);
        $this->store->add($runSource1);
        self::assertCount(2, $this->repository->findAll());

        $runSource2 = new RunSource($parent);
        $this->store->add($runSource2);
        self::assertCount(3, $this->repository->findAll());
    }
}
