<?php

declare(strict_types=1);

namespace App\Tests\Functional\Entity;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Entity\SourceInterface;
use App\Repository\SourceRepository;
use App\Services\SourcePersister;
use App\Tests\Services\SourceRemover;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Uid\Ulid;

class RunSourceTest extends WebTestCase
{
    private SourceRepository $repository;
    private SourcePersister $sourcePersister;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $repository = self::getContainer()->get(SourceRepository::class);
        \assert($repository instanceof SourceRepository);
        $this->repository = $repository;

        $sourcePersister = self::getContainer()->get(SourcePersister::class);
        \assert($sourcePersister instanceof SourcePersister);
        $this->sourcePersister = $sourcePersister;

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        \assert($entityManager instanceof EntityManagerInterface);
        $this->entityManager = $entityManager;

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
        $this->sourcePersister->persist($parent);

        $runSourceId = (string) new Ulid();
        $runSource = new RunSource($runSourceId, $parent);

        $this->sourcePersister->persist($runSource);
        self::assertSame($parent, $runSource->getParent());

        $runSource->unsetParent();
        self::assertNull($runSource->getParent());

        $this->sourcePersister->persist($runSource);
        $this->sourcePersister->remove($parent);
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
                'parent' => new FileSource((string) new Ulid(), (string) new Ulid(), 'label'),
            ],
            SourceInterface::TYPE_GIT => [
                'parent' => new GitSource(
                    (string) new Ulid(),
                    (string) new Ulid(),
                    'https://example.com/repository.git'
                ),
            ],
        ];
    }
}
