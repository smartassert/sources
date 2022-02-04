<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Exception\DirectoryDuplicationException;
use App\Exception\UserGitRepositoryException;
use App\Model\UserGitRepository;
use App\Services\DirectoryDuplicator;
use App\Services\FileStoreManager;
use App\Services\RunSourcePreparer;
use App\Services\SourceSerializer;
use App\Services\UserGitRepositoryPreparer;
use App\Tests\Model\UserId;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Uid\Ulid;

class RunSourcePreparerTest extends WebTestCase
{
    public function testPrepareFileSourceDirectoryDuplicatorThrowsException(): void
    {
        $fileSource = new FileSource(UserId::create(), 'file source label');
        $directoryDuplicatorException = \Mockery::mock(DirectoryDuplicationException::class);

        $runSourcePreparer = new RunSourcePreparer(
            $this->createDirectoryDuplicatorThrowingException((string) $fileSource, $directoryDuplicatorException),
            \Mockery::mock(UserGitRepositoryPreparer::class),
            \Mockery::mock(FileStoreManager::class),
            \Mockery::mock(SourceSerializer::class),
        );

        $runSource = new RunSource($fileSource);

        self::expectExceptionObject($directoryDuplicatorException);

        $runSourcePreparer->prepare($runSource);
    }

    public function testPrepareGitSourceUserGitRepositoryPreparerThrowsException(): void
    {
        $gitSource = new GitSource(UserId::create(), 'http://example.com/repository.git');
        $ref = 'v1.1';

        $gitRepositoryException = \Mockery::mock(UserGitRepositoryException::class);

        $gitRepositoryPreparer = \Mockery::mock(UserGitRepositoryPreparer::class);
        $gitRepositoryPreparer
            ->shouldReceive('prepare')
            ->with($gitSource, $ref)
            ->andThrow($gitRepositoryException)
        ;

        $runSourcePreparer = new RunSourcePreparer(
            \Mockery::mock(DirectoryDuplicator::class),
            $gitRepositoryPreparer,
            \Mockery::mock(FileStoreManager::class),
            \Mockery::mock(SourceSerializer::class),
        );

        $runSource = new RunSource($gitSource, ['ref' => $ref]);

        self::expectExceptionObject($gitRepositoryException);

        $runSourcePreparer->prepare($runSource);
    }

    public function testPrepareGitSourceDirectoryDuplicatorThrowsException(): void
    {
        $gitSource = new GitSource(UserId::create(), 'http://example.com/repository.git', '/directory');
        $ref = 'v1.1';

        $gitRepository = new UserGitRepository($gitSource);

        $gitRepositoryPreparer = \Mockery::mock(UserGitRepositoryPreparer::class);
        $gitRepositoryPreparer
            ->shouldReceive('prepare')
            ->with($gitSource, $ref)
            ->andReturn($gitRepository)
        ;

        $expectedSourcePath = $gitRepository->getPath() . $gitSource->getPath();
        $directoryDuplicatorException = \Mockery::mock(DirectoryDuplicationException::class);

        $runSourcePreparer = new RunSourcePreparer(
            $this->createDirectoryDuplicatorThrowingException($expectedSourcePath, $directoryDuplicatorException),
            $gitRepositoryPreparer,
            \Mockery::mock(FileStoreManager::class),
            \Mockery::mock(SourceSerializer::class),
        );

        $runSource = new RunSource($gitSource, ['ref' => $ref]);

        self::expectExceptionObject($directoryDuplicatorException);

        $runSourcePreparer->prepare($runSource);
    }

    private function createDirectoryDuplicatorThrowingException(
        string $expectedSourcePath,
        DirectoryDuplicationException $exception
    ): DirectoryDuplicator {
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
            ->andThrow($exception)
        ;

        return $directoryDuplicator;
    }
}
