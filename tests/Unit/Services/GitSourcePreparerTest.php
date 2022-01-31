<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services;

use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Exception\File\CreateException;
use App\Exception\File\MirrorException;
use App\Exception\File\NotExistsException;
use App\Exception\File\RemoveException;
use App\Model\UserGitRepository;
use App\Services\GitSourcePreparer;
use App\Services\Source\Factory;
use App\Services\UserGitRepositoryPreparer;
use App\Tests\Mock\Services\MockFileStoreManager;
use App\Tests\Model\UserId;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Exception\IOException;

class GitSourcePreparerTest extends WebTestCase
{
    /**
     * @dataProvider prepareFileStoreMirrorThrowsExceptionDataProvider
     */
    public function testPrepareFileStoreMirrorThrowsException(
        GitSource $gitSource,
        string $ref,
        \Exception $fileStoreManagerException,
        \Exception $expectedException
    ): void {
        $userGitRepository = new UserGitRepository($gitSource);

        $runSource = new RunSource($gitSource, ['ref' => $ref]);

        $sourceFactory = \Mockery::mock(Factory::class);
        $sourceFactory
            ->shouldReceive('createRunSource')
            ->with($gitSource, ['ref' => $ref])
            ->andReturn($runSource)
        ;

        $userGitRepositoryPreparer = $this->createUserGitRepositoryPreparer($gitSource, $ref, $userGitRepository);

        $fileStoreManager = (new MockFileStoreManager())
            ->withMirrorCallThrowingException($fileStoreManagerException)
            ->withRemoveCall((string) $userGitRepository)
            ->getMock()
        ;

        $gitSourcePreparer = new GitSourcePreparer($sourceFactory, $userGitRepositoryPreparer, $fileStoreManager);

        try {
            $gitSourcePreparer->prepare($gitSource, $ref);
            self::fail('Exception not thrown');
        } catch (\Exception $exception) {
            self::assertEquals($expectedException, $exception);
        }
    }

    /**
     * @return array<mixed>
     */
    public function prepareFileStoreMirrorThrowsExceptionDataProvider(): array
    {
        $gitSource = new GitSource(UserId::create(), 'http://example.com/repository.git');
        $ref = 'v1.2';

        $createException = new CreateException(
            '/path/to/target',
            \Mockery::mock(IOException::class)
        );

        $sourceNotExistsException = (new NotExistsException('/path/to/source'))->withContext('source');

        $removeException = new RemoveException(
            '/path/to/target',
            \Mockery::mock(IOException::class)
        );

        $mirrorException = new MirrorException(
            '/path/to/source',
            '/path/to/target',
            \Mockery::mock(IOException::class)
        );

        return [
            'fail to create run source directory' => [
                'gitSource' => $gitSource,
                'ref' => $ref,
                'fileStoreManagerException' => $createException,
                'expectedException' => $createException->withContext('target'),
            ],
            'source directory does not exist' => [
                'gitSource' => $gitSource,
                'ref' => $ref,
                'fileStoreManagerException' => $sourceNotExistsException,
                'expectedException' => $sourceNotExistsException,
            ],
            'target directory cannot be removed' => [
                'gitSource' => $gitSource,
                'ref' => $ref,
                'fileStoreManagerException' => $removeException,
                'expectedException' => $removeException->withContext('target'),
            ],
            'mirror exception' => [
                'gitSource' => $gitSource,
                'ref' => $ref,
                'fileStoreManagerException' => $mirrorException,
                'expectedException' => $mirrorException,
            ],
        ];
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
