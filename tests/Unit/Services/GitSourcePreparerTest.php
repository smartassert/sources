<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services;

use App\Entity\GitSource;
use App\Exception\DirectoryDuplicationException;
use App\Exception\UserGitRepositoryException;
use App\Model\UserGitRepository;
use App\Services\DirectoryDuplicator;
use App\Services\FileStoreManager;
use App\Services\GitSourcePreparer;
use App\Services\UserGitRepositoryPreparer;
use App\Tests\Model\UserId;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Uid\Ulid;

class GitSourcePreparerTest extends WebTestCase
{
    public function testPrepareUserGitRepositoryPreparerThrowsException(): void
    {
        $source = new GitSource(UserId::create(), 'http://example.com/repository.git');
        $ref = 'v1.1';

        $userGitRepositoryException = \Mockery::mock(UserGitRepositoryException::class);

        $userGitRepositoryPreparer = \Mockery::mock(UserGitRepositoryPreparer::class);
        $userGitRepositoryPreparer
            ->shouldReceive('prepare')
            ->with($source, $ref)
            ->andThrow($userGitRepositoryException)
        ;

        $gitSourcePreparer = new GitSourcePreparer(
            $userGitRepositoryPreparer,
            \Mockery::mock(DirectoryDuplicator::class),
            \Mockery::mock(FileStoreManager::class)
        );

        self::expectExceptionObject($userGitRepositoryException);

        $gitSourcePreparer->prepare($source, $ref);
    }

    public function testPrepareDirectoryDuplicatorThrowsException(): void
    {
        $source = new GitSource(UserId::create(), 'http://example.com/repository.git', '/directory');
        $ref = 'v1.1';

        $gitRepository = new UserGitRepository($source);

        $userGitRepositoryPreparer = \Mockery::mock(UserGitRepositoryPreparer::class);
        $userGitRepositoryPreparer
            ->shouldReceive('prepare')
            ->with($source, $ref)
            ->andReturn($gitRepository)
        ;

        $directoryDuplicatorException = \Mockery::mock(DirectoryDuplicationException::class);

        $expectedSourcePath = $gitRepository->getPath() . $source->getPath();

        $directoryDuplicator = \Mockery::mock(DirectoryDuplicator::class);
        $directoryDuplicator
            ->shouldReceive('duplicate')
            ->withArgs(function (string $sourcePath, string $targetPath) use ($expectedSourcePath) {
                self::assertSame($expectedSourcePath, $sourcePath);

                $sourcePathParts = explode('/', $sourcePath);
                $targetPathParts = explode('/', $targetPath);

                self::assertSame($sourcePathParts[0], $targetPathParts[0]);
                self::assertTrue(Ulid::isValid($targetPathParts[1]));

                return true;
            })
            ->andThrow($directoryDuplicatorException)
        ;

        $fileStoreManager = \Mockery::mock(FileStoreManager::class);

        $gitSourcePreparer = new GitSourcePreparer(
            $userGitRepositoryPreparer,
            $directoryDuplicator,
            $fileStoreManager
        );

        self::expectExceptionObject($directoryDuplicatorException);

        $gitSourcePreparer->prepare($source, $ref);
    }
}
