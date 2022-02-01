<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\GitSource;
use App\Model\UserGitRepository;
use App\Services\GitSourcePreparer;
use App\Services\UserGitRepositoryPreparer;
use App\Tests\Model\UserId;
use App\Tests\Services\FileStoreFixtureCreator;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use webignition\ObjectReflector\ObjectReflector;

class GitSourcePreparerTest extends WebTestCase
{
    private const REPOSITORY_URL = 'https://example.com/repository.git';
    private const REF = 'v1.1';
    private const PATH = '/directory';

    private GitSourcePreparer $gitSourcePreparer;
    private FileStoreFixtureCreator $fixtureCreator;
    private string $fileStoreBasePath;

    protected function setUp(): void
    {
        parent::setUp();

        $gitSourcePreparer = self::getContainer()->get(GitSourcePreparer::class);
        \assert($gitSourcePreparer instanceof GitSourcePreparer);
        $this->gitSourcePreparer = $gitSourcePreparer;

        $fixtureCreator = self::getContainer()->get(FileStoreFixtureCreator::class);
        \assert($fixtureCreator instanceof FileStoreFixtureCreator);
        $this->fixtureCreator = $fixtureCreator;

        $fileStoreBasePath = self::getContainer()->getParameter('file_store_base_path');
        \assert(is_string($fileStoreBasePath));
        $this->fileStoreBasePath = $fileStoreBasePath;
    }

    public function testPrepareSuccess(): void
    {
        $gitSource = new GitSource(UserId::create(), self::REPOSITORY_URL, self::PATH);
        $userGitRepository = new UserGitRepository($gitSource);
        $repositoryPath = $this->fileStoreBasePath . '/' . $userGitRepository;

        $userGitRepositoryPreparer = \Mockery::mock(UserGitRepositoryPreparer::class);
        $userGitRepositoryPreparer
            ->shouldReceive('prepare')
            ->withArgs(function (GitSource $passedGitSource, string $passedRef) use ($gitSource, $userGitRepository) {
                self::assertSame($gitSource, $passedGitSource);
                self::assertSame(self::REF, $passedRef);
                $this->fixtureCreator->copyFixturesTo((string) $userGitRepository);

                return true;
            })
            ->andReturn($userGitRepository)
        ;

        ObjectReflector::setProperty(
            $this->gitSourcePreparer,
            GitSourcePreparer::class,
            'userGitRepositoryPreparer',
            $userGitRepositoryPreparer
        );

        $runSource = $this->gitSourcePreparer->prepare($gitSource, self::REF);

        $sourceAbsolutePath = $this->fixtureCreator->getFixturesPath() . $gitSource->getPath();
        $targetAbsolutePath = $this->fileStoreBasePath . '/' . $runSource;

        self::assertSame(scandir($sourceAbsolutePath), scandir($targetAbsolutePath));
        self::assertDirectoryDoesNotExist($repositoryPath);
    }
}
