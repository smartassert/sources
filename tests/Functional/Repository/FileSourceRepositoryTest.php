<?php

declare(strict_types=1);

namespace App\Tests\Functional\Repository;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Entity\SourceInterface;
use App\Repository\FileSourceRepository;
use App\Services\Source\Store;
use App\Tests\Model\UserId;
use App\Tests\Services\EntityRemover;
use SmartAssert\UsersSecurityBundle\Security\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\User\UserInterface;

class FileSourceRepositoryTest extends WebTestCase
{
    private FileSourceRepository $repository;
    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $repository = self::getContainer()->get(FileSourceRepository::class);
        \assert($repository instanceof FileSourceRepository);
        $this->repository = $repository;

        $store = self::getContainer()->get(Store::class);
        \assert($store instanceof Store);
        $this->store = $store;

        $entityRemover = self::getContainer()->get(EntityRemover::class);
        if ($entityRemover instanceof EntityRemover) {
            $entityRemover->removeAll();
        }
    }

    /**
     * @dataProvider findByUserAndTypeDataProvider
     *
     * @param SourceInterface[] $sources
     * @param non-empty-string  $label
     */
    public function testFindOneByUserAndLabel(
        array $sources,
        UserInterface $user,
        string $label,
        ?FileSource $expected
    ): void {
        foreach ($sources as $source) {
            $this->store->add($source);
        }

        self::assertEquals($expected, $this->repository->findOneFileSourceByUserAndLabel($user, $label));
    }

    /**
     * @return array<mixed>
     */
    public function findByUserAndTypeDataProvider(): array
    {
        $matchingFileSourceLabel = 'matching file source label';
        $nonMatchingFileSourceLabel = 'non-matching file source label';

        $userId = UserId::create();
        \assert('' !== $userId);
        $user = new User($userId, 'non-empty string');

        $userFileSources = [
            new FileSource($userId, $matchingFileSourceLabel),
            new FileSource($userId, $nonMatchingFileSourceLabel),
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
                'label' => 'label value',
                'expected' => null,
            ],
            'file source with label match, without user match' => [
                'sources' => [
                    new FileSource(UserId::create(), $matchingFileSourceLabel),
                    new GitSource(UserId::create(), 'https://example.com/repository.git'),
                    new RunSource(
                        new FileSource(UserId::create(), $matchingFileSourceLabel),
                    ),
                ],
                'user' => $user,
                'label' => $matchingFileSourceLabel,
                'expected' => null,
            ],
            'file source with label match, with user match' => [
                'sources' => [
                    $userFileSources[0],
                    $userGitSources[0],
                    $userRunSources[0],
                    $userRunSources[1],
                ],
                'user' => $user,
                'label' => $matchingFileSourceLabel,
                'expected' => $userFileSources[0],
            ],
        ];
    }
}
