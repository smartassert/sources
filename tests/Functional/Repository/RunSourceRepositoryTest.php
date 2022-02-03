<?php

declare(strict_types=1);

namespace App\Tests\Functional\Repository;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Repository\RunSourceRepository;
use App\Tests\Model\UserId;
use App\Tests\Services\EntityRemover;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RunSourceRepositoryTest extends WebTestCase
{
    private RunSourceRepository $repository;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $repository = self::getContainer()->get(RunSourceRepository::class);
        \assert($repository instanceof RunSourceRepository);
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
     * @dataProvider findByParentDataProvider
     *
     * @param RunSource[] $runSources
     */
    public function testFindByParent(array $runSources, FileSource|GitSource $parent, ?RunSource $expected): void
    {
        foreach ($runSources as $runSource) {
            if ($runSource instanceof RunSource) {
                $this->entityManager->persist($runSource);
            }
        }
        $this->entityManager->flush();

        self::assertEquals($expected, $this->repository->findByParent($parent));
    }

    /**
     * @return array<mixed>
     */
    public function findByParentDataProvider(): array
    {
        $fileSource = new FileSource(UserId::create(), 'label');
        $gitSource = new GitSource(UserId::create(), 'http://example.com/repository.git');

        $fileRunSource = new RunSource($fileSource);
        $gitRunSource = new RunSource($gitSource);

        return [
            'no sources' => [
                'runSources' => [],
                'parent' => $fileSource,
                'expected' => null,
            ],
            'no matching sources for file source' => [
                'runSources' => [
                    new RunSource(new FileSource(UserId::create(), 'label')),
                    new RunSource(new FileSource(UserId::create(), '')),
                    new RunSource(new GitSource(UserId::create(), 'http://example.com/repository.git')),
                ],
                'parent' => $fileSource,
                'expected' => null,
            ],
            'no matching sources for git source' => [
                'runSources' => [
                    new RunSource(new FileSource(UserId::create(), 'label')),
                    new RunSource(new FileSource(UserId::create(), '')),
                    new RunSource(new GitSource(UserId::create(), 'http://example.com/repository.git')),
                ],
                'parent' => $gitSource,
                'expected' => null,
            ],
            'matching source for file source' => [
                'runSources' => [
                    new RunSource(new FileSource(UserId::create(), 'label')),
                    new RunSource(new FileSource(UserId::create(), '')),
                    $fileRunSource,
                    new RunSource(new GitSource(UserId::create(), 'http://example.com/repository.git')),
                    $gitRunSource,
                ],
                'parent' => $fileSource,
                'expected' => $fileRunSource,
            ],
            'matching source for git source' => [
                'runSources' => [
                    new RunSource(new FileSource(UserId::create(), 'label')),
                    new RunSource(new FileSource(UserId::create(), '')),
                    $fileRunSource,
                    new RunSource(new GitSource(UserId::create(), 'http://example.com/repository.git')),
                    $gitRunSource,
                ],
                'parent' => $gitSource,
                'expected' => $gitRunSource,
            ],
        ];
    }
}
