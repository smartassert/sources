<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services\Source;

use App\Entity\FileSource;
use App\Entity\RunSource;
use App\Repository\SourceRepository;
use App\Services\EntityIdFactory;
use App\Services\Source\Store;
use App\Tests\Model\UserId;
use App\Tests\Services\EntityRemover;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class StoreTest extends WebTestCase
{
    private Store $store;
    private SourceRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $store = self::getContainer()->get(Store::class);
        \assert($store instanceof Store);
        $this->store = $store;

        $repository = self::getContainer()->get(SourceRepository::class);
        \assert($repository instanceof SourceRepository);
        $this->repository = $repository;

        $entityRemover = self::getContainer()->get(EntityRemover::class);
        if ($entityRemover instanceof EntityRemover) {
            $entityRemover->removeAll();
        }
    }

    public function testAddRunSource(): void
    {
        $idFactory = new EntityIdFactory();

        $parent = new FileSource($idFactory->create(), UserId::create(), 'label');
        $runSource1 = new RunSource($idFactory->create(), $parent);
        $this->store->add($runSource1);
        self::assertCount(2, $this->repository->findAll());

        $runSource2 = new RunSource($idFactory->create(), $parent);
        $this->store->add($runSource2);
        self::assertCount(3, $this->repository->findAll());
    }
}
