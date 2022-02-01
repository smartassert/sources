<?php

declare(strict_types=1);

namespace App\Tests\Functional\Repository;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Entity\RunSourcePreparation;
use App\Repository\RunSourcePreparationRepository;
use App\Tests\Model\UserId;
use App\Tests\Services\EntityRemover;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RunSourcePreparationRepositoryTest extends WebTestCase
{
    private RunSourcePreparationRepository $repository;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $repository = self::getContainer()->get(RunSourcePreparationRepository::class);
        \assert($repository instanceof RunSourcePreparationRepository);
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
     * @dataProvider findByRunSourceDataProvider
     *
     * @param array<RunSource|RunSourcePreparation> $entities
     */
    public function testFindByRunSource(array $entities, RunSource $runSource, ?RunSourcePreparation $expected): void
    {
        foreach ($entities as $entity) {
            $this->entityManager->persist($entity);
        }
        $this->entityManager->flush();

        self::assertEquals($expected, $this->repository->findByRunSource($runSource));
    }

    /**
     * @return array<mixed>
     */
    public function findByRunSourceDataProvider(): array
    {
        $fileSource = new FileSource(UserId::create(), 'label');
        $gitSource = new GitSource(UserId::create(), 'http://example.com/repository.git');

        $fileRunSource = new RunSource($fileSource);
        $gitRunSource = new RunSource($gitSource);

        $fileRunSourcePreparation = new RunSourcePreparation($fileRunSource);
        $gitRunSourcePreparation = new RunSourcePreparation($gitRunSource);

        return [
            'no entities' => [
                'entities' => [],
                'runSource' => $fileRunSource,
                'expected' => null,
            ],
            'no match for file run source' => [
                'entities' => [
                    $fileRunSource,
                    $gitRunSource,
                    new RunSourcePreparation(new RunSource($fileSource)),
                    new RunSourcePreparation(new RunSource($gitSource)),
                ],
                'runSource' => $fileRunSource,
                'expected' => null,
            ],
            'no match for git run source' => [
                'entities' => [
                    $fileRunSource,
                    $gitRunSource,
                    new RunSourcePreparation(new RunSource($fileSource)),
                    new RunSourcePreparation(new RunSource($gitSource)),
                ],
                'runSource' => $gitRunSource,
                'expected' => null,
            ],
            'match for file run source' => [
                'entities' => [
                    $fileRunSource,
                    $gitRunSource,
                    new RunSourcePreparation(new RunSource($fileSource)),
                    $fileRunSourcePreparation,
                    new RunSourcePreparation(new RunSource($gitSource)),
                    $gitRunSourcePreparation,
                ],
                'runSource' => $fileRunSource,
                'expected' => $fileRunSourcePreparation,
            ],
            'match for git run source' => [
                'entities' => [
                    $fileRunSource,
                    $gitRunSource,
                    new RunSourcePreparation(new RunSource($fileSource)),
                    $fileRunSourcePreparation,
                    new RunSourcePreparation(new RunSource($gitSource)),
                    $gitRunSourcePreparation,
                ],
                'runSource' => $gitRunSource,
                'expected' => $gitRunSourcePreparation,
            ],
        ];
    }
}
