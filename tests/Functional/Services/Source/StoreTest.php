<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services\Source;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Entity\SourceInterface;
use App\Repository\SourceRepository;
use App\Services\Source\Store;
use App\Tests\Services\Source\SourceRemover;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Uid\Ulid;

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

        $sourceRemover = self::getContainer()->get(SourceRemover::class);
        if ($sourceRemover instanceof SourceRemover) {
            $sourceRemover->removeAll();
        }
    }

    /**
     * @dataProvider removeDataProvider
     *
     * @param callable(Store): SourceInterface $sourceCreator
     */
    public function testRemoveNonEncapsulatingSource(callable $sourceCreator): void
    {
        $source = $sourceCreator($this->store);

        self::assertCount(1, $this->repository->findAll());

        $this->store->remove($source);

        self::assertCount(0, $this->repository->findAll());
    }

    /**
     * @return array<mixed>
     */
    public function removeDataProvider(): array
    {
        return [
            FileSource::class => [
                'sourceCreator' => function (Store $store): SourceInterface {
                    $source = new FileSource((string) new Ulid(), (string) new Ulid(), 'label');
                    $store->add($source);

                    return $source;
                },
            ],
            GitSource::class => [
                'sourceCreator' => function (Store $store): SourceInterface {
                    $source = new GitSource(
                        (string) new Ulid(),
                        (string) new Ulid(),
                        'https://example.com/repository.git'
                    );

                    $store->add($source);

                    return $source;
                },
            ],
            RunSource::class => [
                'sourceCreator' => function (Store $store): SourceInterface {
                    $parent = new FileSource((string) new Ulid(), (string) new Ulid(), 'label');
                    $source = new RunSource((string) new Ulid(), $parent);
                    $source->unsetParent();

                    $store->add($source);

                    return $source;
                },
            ],
        ];
    }

    public function testRemoveRunSource(): void
    {
        $parent = new FileSource((string) new Ulid(), (string) new Ulid(), 'label');
        $this->store->add($parent);

        $source = new RunSource((string) new Ulid(), $parent);
        $this->store->add($source);

        self::assertCount(2, $this->repository->findAll());

        $this->store->remove($source);

        self::assertSame([$source], $this->repository->findAll());
    }
}
