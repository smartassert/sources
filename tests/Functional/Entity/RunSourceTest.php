<?php

declare(strict_types=1);

namespace App\Tests\Functional\Entity;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Entity\SourceInterface;
use App\Repository\SourceRepository;
use App\Tests\Services\SourceRemover;
use App\Tests\Services\TestSourcePersister;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Uid\Ulid;

class RunSourceTest extends WebTestCase
{
    private SourceRepository $repository;
    private TestSourcePersister $sourcePersister;

    protected function setUp(): void
    {
        parent::setUp();

        $repository = self::getContainer()->get(SourceRepository::class);
        \assert($repository instanceof SourceRepository);
        $this->repository = $repository;

        $sourcePersister = self::getContainer()->get(TestSourcePersister::class);
        \assert($sourcePersister instanceof TestSourcePersister);
        $this->sourcePersister = $sourcePersister;

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
        $this->sourcePersister->detach($parent);
        $this->sourcePersister->detach($runSource);

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
