<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services;

use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Exception\DirectoryDuplicationException;
use App\Exception\UserGitRepositoryException;
use App\Model\UserGitRepository;
use App\Services\DirectoryDuplicator;
use App\Services\FileStoreManager;
use App\Services\GitSourcePreparer;
use App\Services\Source\Factory;
use App\Services\UserGitRepositoryPreparer;
use App\Tests\Model\UserId;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GitSourcePreparerTest extends WebTestCase
{
    public function testPrepareUserGitRepositoryPreparerThrowsException(): void
    {
        $source = new GitSource(UserId::create(), 'http://example.com/repository.git');
        $ref = 'v1.1';
        $target = new RunSource($source);

        $sourceFactory = \Mockery::mock(Factory::class);
        $sourceFactory
            ->shouldReceive('createRunSource')
            ->with($source)
            ->andReturn($target)
        ;

        $userGitRepositoryException = \Mockery::mock(UserGitRepositoryException::class);

        $userGitRepositoryPreparer = \Mockery::mock(UserGitRepositoryPreparer::class);
        $userGitRepositoryPreparer
            ->shouldReceive('prepare')
            ->with($source, $ref)
            ->andThrow($userGitRepositoryException)
        ;

        $gitSourcePreparer = new GitSourcePreparer(
            $sourceFactory,
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
        $target = new RunSource($source);

        $sourceFactory = \Mockery::mock(Factory::class);
        $sourceFactory
            ->shouldReceive('createRunSource')
            ->with($source)
            ->andReturn($target)
        ;

        $gitRepository = new UserGitRepository($source);

        $userGitRepositoryPreparer = \Mockery::mock(UserGitRepositoryPreparer::class);
        $userGitRepositoryPreparer
            ->shouldReceive('prepare')
            ->with($source, $ref)
            ->andReturn($gitRepository)
        ;

        $directoryDuplicatorException = \Mockery::mock(DirectoryDuplicationException::class);

        $directoryDuplicator = \Mockery::mock(DirectoryDuplicator::class);
        $directoryDuplicator
            ->shouldReceive('duplicate')
            ->with($gitRepository->getPath() . $source->getPath(), (string) $target)
            ->andThrow($directoryDuplicatorException)
        ;

        $fileStoreManager = \Mockery::mock(FileStoreManager::class);

        $gitSourcePreparer = new GitSourcePreparer(
            $sourceFactory,
            $userGitRepositoryPreparer,
            $directoryDuplicator,
            $fileStoreManager
        );

        self::expectExceptionObject($directoryDuplicatorException);

        $gitSourcePreparer->prepare($source, $ref);
    }
}
