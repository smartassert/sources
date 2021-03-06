<?php

declare(strict_types=1);

namespace App\Tests\Functional\Repository;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Entity\SourceInterface;
use App\Enum\Source\Type;
use App\Repository\SourceRepository;
use App\Services\Source\Store;
use App\Tests\Model\UserId;
use App\Tests\Services\EntityRemover;
use Doctrine\ORM\EntityManagerInterface;
use SmartAssert\UsersSecurityBundle\Security\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\User\UserInterface;

class SourceRepositoryTest extends WebTestCase
{
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

        $entityRemover = self::getContainer()->get(EntityRemover::class);
        if ($entityRemover instanceof EntityRemover) {
            $entityRemover->removeAll();
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
                    UserId::create(),
                    'https://example.com/repository.git',
                    '/',
                    ''
                ),
            ],
            FileSource::class => [
                'source' => new FileSource(UserId::create(), 'file source label'),
            ],
            RunSource::class => [
                'source' => new RunSource(
                    new FileSource(UserId::create(), 'file source label')
                ),
            ],
        ];
    }

    /**
     * @dataProvider findByUserAndTypeDataProvider
     *
     * @param SourceInterface[] $sources
     * @param Type[]            $types
     * @param SourceInterface[] $expected
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
        $userId = UserId::create();
        $user = new User($userId);

        $userFileSources = [
            new FileSource($userId, 'file source label'),
        ];

        $userGitSources = [
            new GitSource($userId, 'https://example.com/repository.git'),
        ];

        $userRunSources = [
            new RunSource($userFileSources[0]),
            new RunSource($userGitSources[0]),
        ];

        return [
            'no sources' => [
                'sources' => [],
                'user' => $user,
                'types' => [
                    Type::FILE,
                    Type::GIT,
                    Type::RUN,
                ],
                'expected' => [],
            ],
            'has file, git and run sources, no user match' => [
                'sources' => [
                    new FileSource(UserId::create(), 'file source label'),
                    new GitSource(UserId::create(), 'https://example.com/repository.git'),
                    new RunSource(
                        new FileSource(UserId::create(), 'file source label'),
                    ),
                ],
                'user' => $user,
                'types' => [
                    Type::FILE,
                    Type::GIT,
                    Type::RUN,
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
                    Type::FILE,
                    Type::GIT,
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
                    Type::FILE,
                    Type::GIT,
                ],
                'expected' => [
                    $userFileSources[0],
                    $userGitSources[0],
                ],
            ],
            'has file, git and run sources for mixed users' => [
                'sources' => [
                    $userFileSources[0],
                    new FileSource(UserId::create(), 'file source label'),
                    $userGitSources[0],
                    new GitSource(UserId::create(), 'https://example.com/repository.git'),
                    $userRunSources[0],
                    $userRunSources[1],
                    new RunSource(
                        new FileSource(UserId::create(), 'file source label')
                    ),
                    new RunSource(
                        new GitSource(UserId::create(), 'https://example.com/repository.git')
                    )
                ],
                'user' => $user,
                'types' => [
                    Type::FILE,
                    Type::GIT,
                ],
                'expected' => [
                    $userFileSources[0],
                    $userGitSources[0],
                ],
            ],
        ];
    }
}
