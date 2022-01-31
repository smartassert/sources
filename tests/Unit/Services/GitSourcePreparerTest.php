<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services;

use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Exception\File\CreateException;
use App\Exception\SourceMirrorException;
use App\Model\UserGitRepository;
use App\Services\GitSourcePreparer;
use App\Services\UserGitRepositoryPreparer;
use App\Tests\Mock\Services\MockFileStoreManager;
use App\Tests\Model\UserId;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Exception\IOException;

class GitSourcePreparerTest extends WebTestCase
{
    public function testPrepareFileStoreMirrorThrowsException(): void
    {
        $gitSource = new GitSource(UserId::create(), 'http://example.com/repository.git');
        $ref = 'v1.2';

        $fileStoreManagerException = (new CreateException(
            '/path/to/target',
            \Mockery::mock(IOException::class)
        ))->withContext('target');

        $userGitRepository = new UserGitRepository($gitSource);
        $userGitRepositoryPreparer = $this->createUserGitRepositoryPreparer($gitSource, $ref, $userGitRepository);

        $fileStoreManager = (new MockFileStoreManager())
            ->withMirrorCallThrowingException($fileStoreManagerException)
            ->withRemoveCall((string) $userGitRepository)
            ->getMock()
        ;

        $gitSourcePreparer = new GitSourcePreparer($userGitRepositoryPreparer, $fileStoreManager);

        try {
            $gitSourcePreparer->prepare(new RunSource($gitSource), $ref);
            self::fail('Exception not thrown');
        } catch (\Exception $exception) {
            self::assertEquals(new SourceMirrorException($fileStoreManagerException), $exception);
        }
    }

    private function createUserGitRepositoryPreparer(
        GitSource $expectedGitSource,
        ?string $expectedRef,
        UserGitRepository $outcome
    ): UserGitRepositoryPreparer {
        $mock = \Mockery::mock(UserGitRepositoryPreparer::class);

        $mock
            ->shouldReceive('prepare')
            ->withArgs(function (GitSource $gitSource, ?string $ref) use ($expectedGitSource, $expectedRef) {
                self::assertSame($expectedGitSource, $gitSource);
                self::assertSame($expectedRef, $ref);

                return true;
            })
            ->andReturn($outcome)
        ;

        return $mock;
    }
}
