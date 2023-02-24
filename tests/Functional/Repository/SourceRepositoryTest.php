<?php

declare(strict_types=1);

namespace App\Tests\Functional\Repository;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Entity\SourceInterface;
use App\Enum\Source\Type;
use App\Repository\SourceRepository;
use App\Services\EntityIdFactory;
use App\Tests\Model\UserId;
use App\Tests\Services\EntityRemover;
use SmartAssert\UsersSecurityBundle\Security\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\User\UserInterface;

class SourceRepositoryTest extends WebTestCase
{
    private SourceRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $repository = self::getContainer()->get(SourceRepository::class);
        \assert($repository instanceof SourceRepository);
        $this->repository = $repository;

        $entityRemover = self::getContainer()->get(EntityRemover::class);
        if ($entityRemover instanceof EntityRemover) {
            $entityRemover->removeAll();
        }
    }

    /**
     * @dataProvider findNonDeletedByUserAndTypeDataProvider
     *
     * @param SourceInterface[] $sources
     * @param Type[]            $types
     * @param SourceInterface[] $expected
     */
    public function testFindNonDeletedByUserAndType(
        array $sources,
        UserInterface $user,
        array $types,
        array $expected
    ): void {
        foreach ($sources as $source) {
            $this->repository->save($source);
        }

        self::assertEquals($expected, $this->repository->findNonDeletedByUserAndType($user, $types));
    }

    /**
     * @return array<mixed>
     */
    public function findNonDeletedByUserAndTypeDataProvider(): array
    {
        $idFactory = new EntityIdFactory();

        $userId = UserId::create();
        \assert('' !== $userId);
        $user = new User($userId, 'non-empty string');

        $userFileSources = [
            'deletedAt=null' => new FileSource($idFactory->create(), $userId, 'file source label'),
            'deletedAt=-1s' => (function () use ($userId, $idFactory) {
                $source = new FileSource($idFactory->create(), $userId, 'file source label');
                $source->setDeletedAt(new \DateTimeImmutable('-1 second'));

                return $source;
            })(),
            'deletedAt=+1s' => (function () use ($userId, $idFactory) {
                $source = new FileSource($idFactory->create(), $userId, 'file source label');
                $source->setDeletedAt(new \DateTimeImmutable('1 second'));

                return $source;
            })(),
        ];

        $userGitSources = [
            'deletedAt=null' => new GitSource(
                $idFactory->create(),
                $userId,
                'label',
                'https://example.com/repository.git'
            ),
        ];

        $userRunSources = [
            'parent=file,deletedAt=null' => new RunSource($idFactory->create(), $userFileSources['deletedAt=null']),
            'parent=git,deletedAt=null' => new RunSource($idFactory->create(), $userGitSources['deletedAt=null']),
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
                    new FileSource($idFactory->create(), UserId::create(), 'file source label'),
                    new GitSource(
                        $idFactory->create(),
                        UserId::create(),
                        'label',
                        'https://example.com/repository.git'
                    ),
                    new RunSource(
                        $idFactory->create(),
                        new FileSource($idFactory->create(), UserId::create(), 'file source label'),
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
                    $userFileSources['deletedAt=null'],
                    $userGitSources['deletedAt=null'],
                ],
                'user' => $user,
                'types' => [
                    Type::FILE,
                    Type::GIT,
                ],
                'expected' => [
                    $userFileSources['deletedAt=null'],
                    $userGitSources['deletedAt=null'],
                ],
            ],
            'has file, git and run sources for correct user only' => [
                'sources' => [
                    $userFileSources['deletedAt=null'],
                    $userGitSources['deletedAt=null'],
                    $userRunSources['parent=file,deletedAt=null'],
                    $userRunSources['parent=git,deletedAt=null'],
                ],
                'user' => $user,
                'types' => [
                    Type::FILE,
                    Type::GIT,
                ],
                'expected' => [
                    $userFileSources['deletedAt=null'],
                    $userGitSources['deletedAt=null'],
                ],
            ],
            'has file, git and run sources for mixed users' => [
                'sources' => [
                    $userFileSources['deletedAt=null'],
                    new FileSource($idFactory->create(), UserId::create(), 'file source label'),
                    $userGitSources['deletedAt=null'],
                    new GitSource(
                        $idFactory->create(),
                        UserId::create(),
                        'label',
                        'https://example.com/repository.git'
                    ),
                    $userRunSources['parent=file,deletedAt=null'],
                    $userRunSources['parent=git,deletedAt=null'],
                    new RunSource(
                        $idFactory->create(),
                        new FileSource($idFactory->create(), UserId::create(), 'file source label')
                    ),
                    new RunSource(
                        $idFactory->create(),
                        new GitSource(
                            $idFactory->create(),
                            UserId::create(),
                            'label',
                            'https://example.com/repository.git'
                        )
                    )
                ],
                'user' => $user,
                'types' => [
                    Type::FILE,
                    Type::GIT,
                ],
                'expected' => [
                    $userFileSources['deletedAt=null'],
                    $userGitSources['deletedAt=null'],
                ],
            ],
            'file sources, deletedAt=null,+1s,-1s' => [
                'sources' => [
                    $userFileSources['deletedAt=null'],
                    $userFileSources['deletedAt=-1s'],
                    $userFileSources['deletedAt=+1s']
                ],
                'user' => $user,
                'types' => [
                    Type::FILE,
                ],
                'expected' => [
                    $userFileSources['deletedAt=null'],
                ],
            ],
        ];
    }
}
