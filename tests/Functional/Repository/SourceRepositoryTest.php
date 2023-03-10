<?php

declare(strict_types=1);

namespace App\Tests\Functional\Repository;

use App\Entity\SourceInterface;
use App\Enum\Source\Type;
use App\Repository\SourceRepository;
use App\Tests\Model\UserId;
use App\Tests\Services\EntityRemover;
use App\Tests\Services\SourceOriginFactory;
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
        $userId = UserId::create();
        $user = new User($userId, 'non-empty string');

        $userFileSources = [
            'deletedAt=null' => SourceOriginFactory::create(type: 'file', userId: $userId),
            'deletedAt=-1s' => (function () use ($userId): SourceInterface {
                $source = SourceOriginFactory::create(type: 'file', userId: $userId);
                $source->setDeletedAt(new \DateTimeImmutable('-1 second'));

                return $source;
            })(),
            'deletedAt=+1s' => (function () use ($userId): SourceInterface {
                $source = SourceOriginFactory::create(type: 'file', userId: $userId);
                $source->setDeletedAt(new \DateTimeImmutable('1 second'));

                return $source;
            })(),
        ];

        $userGitSources = [
            'deletedAt=null' => SourceOriginFactory::create(type: 'git', userId: $userId),
        ];

        return [
            'no sources' => [
                'sources' => [],
                'user' => $user,
                'types' => [
                    Type::FILE,
                    Type::GIT,
                ],
                'expected' => [],
            ],
            'has file and git sources, no user match' => [
                'sources' => [
                    SourceOriginFactory::create(type: 'file'),
                    SourceOriginFactory::create(type: 'git'),
                ],
                'user' => $user,
                'types' => [
                    Type::FILE,
                    Type::GIT,
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
            'has file and git for correct user only' => [
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
            'has file and git sources for mixed users' => [
                'sources' => [
                    $userFileSources['deletedAt=null'],
                    SourceOriginFactory::create(type: 'file'),
                    $userGitSources['deletedAt=null'],
                    SourceOriginFactory::create(type: 'git'),
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
