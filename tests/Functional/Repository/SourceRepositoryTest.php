<?php

declare(strict_types=1);

namespace App\Tests\Functional\Repository;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Entity\SourceInterface;
use App\Repository\SourceRepository;
use App\Services\SourceFactory;
use App\Tests\Services\SourceRemover;
use App\Tests\Services\TestSourcePersister;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SourceRepositoryTest extends WebTestCase
{
    private const USER_ID = '01FPSVJ7ZT85X73BW05EK9B3XG';

    private SourceFactory $sourceFactory;
    private SourceRepository $repository;
    private TestSourcePersister $sourcePersister;

    protected function setUp(): void
    {
        parent::setUp();

        $gitSourceFactory = self::getContainer()->get(SourceFactory::class);
        \assert($gitSourceFactory instanceof SourceFactory);
        $this->sourceFactory = $gitSourceFactory;

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
     * @dataProvider persistAndRetrieveDataProvider
     *
     * @param callable(SourceFactory): SourceInterface $sourceCreator
     */
    public function testPersistAndRetrieveSource(callable $sourceCreator): void
    {
        $source = $sourceCreator($this->sourceFactory);
        $sourceId = $source->getId();

        $this->sourcePersister->persist($source);
        $this->sourcePersister->detach($source);

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

                    return $factory->createRunSource($parent);
                },
            ],
        ];
    }
}
