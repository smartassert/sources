<?php

declare(strict_types=1);

namespace App\Tests\Functional\Entity;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Entity\SourceInterface;
use App\Enum\RunSourcePreparationState;
use App\Repository\SourceRepository;
use App\Services\Source\Store;
use App\Tests\Model\UserId;
use App\Tests\Services\Source\SourceRemover;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RunSourceTest extends WebTestCase
{
    private SourceRepository $repository;
    private Store $store;
    private EntityManagerInterface $entityManager;
    private SourceRepository $sourceRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $repository = self::getContainer()->get(SourceRepository::class);
        \assert($repository instanceof SourceRepository);
        $this->repository = $repository;

        $store = self::getContainer()->get(Store::class);
        \assert($store instanceof Store);
        $this->store = $store;

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        \assert($entityManager instanceof EntityManagerInterface);
        $this->entityManager = $entityManager;

        $sourceRepository = self::getContainer()->get(SourceRepository::class);
        \assert($sourceRepository instanceof SourceRepository);
        $this->sourceRepository = $sourceRepository;

        $sourceRemover = self::getContainer()->get(SourceRemover::class);
        if ($sourceRemover instanceof SourceRemover) {
            $sourceRemover->removeAll();
        }
    }

    /**
     * @dataProvider deleteParentDataProvider
     */
    public function testDeleteParent(FileSource|GitSource $parent): void
    {
        $this->store->add($parent);

        $runSource = new RunSource($parent);
        $runSourceId = $runSource->getId();

        $this->store->add($runSource);
        self::assertSame($parent, $runSource->getParent());

        $runSource->unsetParent();
        self::assertNull($runSource->getParent());

        $this->store->add($runSource);
        $this->store->remove($parent);
        $this->entityManager->detach($parent);
        $this->entityManager->detach($runSource);

        $retrievedRunSource = $this->repository->find($runSourceId);

        self::assertEquals($runSource, $retrievedRunSource);
    }

    /**
     * @return array<mixed>
     */
    public function deleteParentDataProvider(): array
    {
        return [
            SourceInterface::TYPE_FILE => [
                'parent' => new FileSource(UserId::create(), 'label'),
            ],
            SourceInterface::TYPE_GIT => [
                'parent' => new GitSource(UserId::create(), 'https://example.com/repository.git'),
            ],
        ];
    }

    /**
     * @dataProvider persistPreparationStateDataProvider
     */
    public function testPersistPreparationState(RunSourcePreparationState $state): void
    {
        self::assertCount(0, $this->sourceRepository->findAll());

        $source = new FileSource(UserId::create(), 'label');
        $runSource = new RunSource($source, [], $state);
        $runSourceId = $runSource->getId();

        $this->entityManager->persist($runSource);
        $this->entityManager->flush();

        self::assertCount(2, $this->sourceRepository->findAll());

        $this->entityManager->clear();

        $retrievedRunSource = $this->sourceRepository->find($runSourceId);
        self::assertInstanceOf(RunSource::class, $retrievedRunSource);
        self::assertEquals($runSource, $retrievedRunSource);
        self::assertEquals($state, $retrievedRunSource->getPreparationState());
    }

    /**
     * @return array<mixed>
     */
    public function persistPreparationStateDataProvider(): array
    {
        return [
            RunSourcePreparationState::UNKNOWN->value => [
                'state' => RunSourcePreparationState::UNKNOWN,
            ],
            RunSourcePreparationState::FAILED->value => [
                'state' => RunSourcePreparationState::FAILED,
            ],
            RunSourcePreparationState::REQUESTED->value => [
                'state' => RunSourcePreparationState::REQUESTED,
            ],
            RunSourcePreparationState::PREPARING->value => [
                'state' => RunSourcePreparationState::PREPARING,
            ],
            RunSourcePreparationState::PREPARED->value => [
                'state' => RunSourcePreparationState::PREPARED,
            ],
        ];
    }
}
