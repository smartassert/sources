<?php

declare(strict_types=1);

namespace App\Tests\Functional\Repository;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Entity\SourceInterface;
use App\Repository\SourceRepository;
use App\Services\Source\Store;
use App\Tests\Services\Source\SourceRemover;
use Doctrine\ORM\EntityManagerInterface;
use SmartAssert\UsersSecurityBundle\Security\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Ulid;

class SourceRepositoryTest extends WebTestCase
{
    private const USER_ID = '01FPSVJ7ZT85X73BW05EK9B3XG';

    private SourceRepository $repository;
    private Store $store;
    private EntityManagerInterface $entityManager;

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

        $sourceRemover = self::getContainer()->get(SourceRemover::class);
        if ($sourceRemover instanceof SourceRemover) {
            $sourceRemover->removeAll();
        }
    }

    /**
     * @dataProvider persistAndRetrieveDataProvider
     */
    public function testPersistAndRetrieveSource(SourceInterface $source): void
    {
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
                'source' => new GitSource(
                    (string) new Ulid(),
                    self::USER_ID,
                    'https://example.com/repository.git',
                    '/',
                    null
                ),
            ],
            FileSource::class => [
                'source' => new FileSource(
                    (string) new Ulid(),
                    self::USER_ID,
                    'file source label'
                ),
            ],
            RunSource::class => [
                'source' => new RunSource(
                    (string) new Ulid(),
                    new FileSource(
                        (string) new Ulid(),
                        self::USER_ID,
                        'file source label'
                    )
                ),
            ],
        ];
    }

    /**
     * @dataProvider findByUserAndTypeDataProvider
     *
     * @param SourceInterface[]              $sources
     * @param array<SourceInterface::TYPE_*> $types
     * @param SourceInterface[]              $expected
     */
    public function testFindByUserAndType(array $sources, UserInterface $user, array $types, array $expected): void
    {
        foreach ($sources as $source) {
            $this->store->add($source);
        }

        self::assertEquals($expected, $this->repository->findByUserAndType($user, $types));
    }

    /**
     * @return array<mixed>
     */
    public function findByUserAndTypeDataProvider(): array
    {
        $userId = (string) new Ulid();
        $user = new User($userId);

        $userFileSources = [
            new FileSource((string) new Ulid(), $userId, 'file source label'),
        ];

        $userGitSources = [
            new GitSource((string) new Ulid(), $userId, 'https://example.com/repository.git'),
        ];

        $userRunSources = [
            new RunSource((string) new Ulid(), $userFileSources[0]),
            new RunSource((string) new Ulid(), $userGitSources[0]),
        ];

        return [
            'no sources' => [
                'sources' => [],
                'user' => $user,
                'types' => [
                    SourceInterface::TYPE_FILE,
                    SourceInterface::TYPE_GIT,
                    SourceInterface::TYPE_RUN,
                ],
                'expected' => [],
            ],
            'has file, git and run sources, no user match' => [
                'sources' => [
                    new FileSource((string) new Ulid(), (string) new Ulid(), 'file source label'),
                    new GitSource((string) new Ulid(), (string) new Ulid(), 'https://example.com/repository.git'),
                    new RunSource(
                        (string) new Ulid(),
                        new FileSource((string) new Ulid(), (string) new Ulid(), 'file source label'),
                    ),
                ],
                'user' => $user,
                'types' => [
                    SourceInterface::TYPE_FILE,
                    SourceInterface::TYPE_GIT,
                    SourceInterface::TYPE_RUN,
                ],
                'expected' => [],
            ],
            'has file and git sources for correct user only' => [
                'sources' => [
                    $userFileSources[0],
                    $userGitSources[0],
                ],
                'user' => $user,
                'types' => [
                    SourceInterface::TYPE_FILE,
                    SourceInterface::TYPE_GIT,
                ],
                'expected' => [
                    $userFileSources[0],
                    $userGitSources[0],
                ],
            ],
            'has file, git and run sources for correct user only' => [
                'sources' => [
                    $userFileSources[0],
                    $userGitSources[0],
                    $userRunSources[0],
                    $userRunSources[1],
                ],
                'user' => $user,
                'types' => [
                    SourceInterface::TYPE_FILE,
                    SourceInterface::TYPE_GIT,
                ],
                'expected' => [
                    $userFileSources[0],
                    $userGitSources[0],
                ],
            ],
            'has file, git and run sources for mixed users' => [
                'sources' => [
                    $userFileSources[0],
                    new FileSource((string) new Ulid(), (string) new Ulid(), 'file source label'),
                    $userGitSources[0],
                    new GitSource((string) new Ulid(), (string) new Ulid(), 'https://example.com/repository.git'),
                    $userRunSources[0],
                    $userRunSources[1],
                    new RunSource(
                        (string) new Ulid(),
                        new FileSource((string) new Ulid(), (string) new Ulid(), 'file source label')
                    ),
                    new RunSource(
                        (string) new Ulid(),
                        new GitSource((string) new Ulid(), (string) new Ulid(), 'https://example.com/repository.git')
                    )
                ],
                'user' => $user,
                'types' => [
                    SourceInterface::TYPE_FILE,
                    SourceInterface::TYPE_GIT,
                ],
                'expected' => [
                    $userFileSources[0],
                    $userGitSources[0],
                ],
            ],
        ];
    }
}
