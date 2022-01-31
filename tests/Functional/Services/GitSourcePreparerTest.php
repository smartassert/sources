<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Exception\GitActionException;
use App\Exception\UserGitRepositoryException;
use App\Model\UserGitRepository;
use App\Services\GitSourcePreparer;
use App\Services\UserGitRepositoryPreparer;
use App\Tests\Model\UserId;
use App\Tests\Services\FileStoreFixtureCreator;
use App\Tests\Services\Source\SourceRemover;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Path;
use webignition\ObjectReflector\ObjectReflector;

class GitSourcePreparerTest extends WebTestCase
{
    private const REPOSITORY_URL = 'https://example.com/repository.git';
    private const REF = 'v1.1';
    private const PATH = '/directory';

    private GitSourcePreparer $gitSourcePreparer;
    private FileStoreFixtureCreator $fixtureCreator;
    private string $fileStoreBasePath;
    private GitSource $gitSource;
    private UserGitRepository $userGitRepository;
    private string $repositoryPath;

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

        $sourceRemover = self::getContainer()->get(SourceRemover::class);
        if ($sourceRemover instanceof SourceRemover) {
            $sourceRemover->removeAll();
        }

        $this->gitSource = new GitSource(UserId::create(), self::REPOSITORY_URL, self::PATH);
        $this->userGitRepository = new UserGitRepository($this->gitSource);
        $this->repositoryPath = $this->fileStoreBasePath . '/' . $this->userGitRepository;
    }

    public function testPrepareUserGitRepositoryPreparerThrowsException(): void
    {
        $userGitRepositoryException = new UserGitRepositoryException(
            $this->userGitRepository,
            GitActionException::createFromCloneOutput(sprintf(
                'Cloning into %s' . "\n" . 'Clone error content',
                $this->repositoryPath,
            ))
        );

        $this->setUserGitRepositoryPreparerOutcome($this->gitSource, self::REF, $userGitRepositoryException);

        try {
            $this->gitSourcePreparer->prepare(new RunSource($this->gitSource), self::REF);
            self::fail(UserGitRepositoryException::class . ' not thrown');
        } catch (UserGitRepositoryException $exception) {
            self::assertSame($userGitRepositoryException, $exception);
        }

        self::assertDirectoryDoesNotExist($this->repositoryPath);
    }

    public function testPrepareSuccess(): void
    {
        $this->setUserGitRepositoryPreparerOutcome($this->gitSource, self::REF, $this->userGitRepository);

        $runSource = new RunSource($this->gitSource);
        $this->gitSourcePreparer->prepare($runSource, self::REF);

        $sourceAbsolutePath = Path::canonicalize(
            $this->fixtureCreator->getFixturesPath() . $this->gitSource->getPath()
        );
        $targetAbsolutePath = Path::canonicalize($this->fileStoreBasePath . '/' . $runSource);

        self::assertSame(scandir($sourceAbsolutePath), scandir($targetAbsolutePath));
        self::assertDirectoryDoesNotExist($this->repositoryPath);
    }

    private function setUserGitRepositoryPreparerOutcome(
        GitSource $expectedGitSource,
        ?string $expectedRef,
        UserGitRepositoryException|UserGitRepository $outcome
    ): void {
        ObjectReflector::setProperty(
            $this->gitSourcePreparer,
            GitSourcePreparer::class,
            'userGitRepositoryPreparer',
            $this->createUserGitRepositoryPreparer($expectedGitSource, $expectedRef, $outcome)
        );
    }

    private function createUserGitRepositoryPreparer(
        GitSource $expectedGitSource,
        ?string $expectedRef,
        UserGitRepositoryException|UserGitRepository $outcome
    ): UserGitRepositoryPreparer {
        $mock = \Mockery::mock(UserGitRepositoryPreparer::class);

        $expectation = $mock
            ->shouldReceive('prepare')
            ->withArgs(function (GitSource $gitSource, ?string $ref) use ($expectedGitSource, $expectedRef, $outcome) {
                self::assertSame($expectedGitSource, $gitSource);
                self::assertSame($expectedRef, $ref);

                if ($outcome instanceof UserGitRepository) {
                    $this->fixtureCreator->copyFixturesTo((string) $this->userGitRepository);
                }

                return true;
            })
        ;

        if ($outcome instanceof UserGitRepositoryException) {
            $expectation->andThrow($outcome);
        } else {
            $expectation->andReturn($outcome);
        }

        return $mock;
    }
}
