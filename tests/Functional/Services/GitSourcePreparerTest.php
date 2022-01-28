<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\GitSource;
use App\Exception\GitActionException;
use App\Exception\ProcessExecutorException;
use App\Model\ProcessOutput;
use App\Services\GitRepositoryCheckoutHandler;
use App\Services\GitRepositoryCloner;
use App\Services\GitSourcePreparer;
use App\Tests\Mock\Services\MockGitRepositoryCheckoutHandler;
use App\Tests\Mock\Services\MockGitRepositoryCloner;
use App\Tests\Model\UserId;
use App\Tests\Services\FileStoreFixtureCreator;
use App\Tests\Services\Source\SourceRemover;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Process\Exception\RuntimeException as SymfonyProcessRuntimeException;
use Symfony\Component\String\UnicodeString;
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
    private string $repositoryPath = '';

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
    }

    public function testPrepareCloneProcessThrowsException(): void
    {
        $this->doPrepareWithExpectedGitCloneExceptionTest(
            new ProcessExecutorException(
                new SymfonyProcessRuntimeException('Unable to launch a new process.')
            ),
            function (ProcessExecutorException $cloneProcessException, GitActionException $gitCloneException) {
                self::assertSame('Git clone process failed', $gitCloneException->getMessage());
                self::assertSame($cloneProcessException, $gitCloneException->getProcessExecutorException());
            }
        );
    }

    public function testPrepareCloneProcessCannotClone(): void
    {
        $this->doPrepareWithExpectedGitCloneExceptionTest(
            $this->createCloneProcessErrorOutput(
                $this->createCloneMessageForRepositoryNotFound()
            ),
            function (ProcessOutput $cloneProcessOutput, GitActionException $gitCloneException) {
                self::assertStringEndsWith($gitCloneException->getMessage(), $cloneProcessOutput->getErrorOutput());
            }
        );
    }

//    public function testPrepareCheckoutProcessThrowsException(): void
//    {
//        $this->setGitRepositoryClonerOutcome(new ProcessOutput(0, 'clone success output', ''));
//
//        $this->setGitRepositoryCheckoutHandlerOutcome(new ProcessOutput(0, 'checkout output', ''));
//
//        new ProcessExecutorException(
//            new RuntimeException(sprintf('The provided cwd "%s" does not exist', __DIR__ . '/does-not-exist'))
//        );
//
//        $runSource = $this->gitSourcePreparer->prepare($this->gitSource, self::REF);
//
//        self::assertDirectoryDoesNotExist($this->repositoryPath);
//
//        $sourceAbsolutePath = Path::canonicalize(
//            $this->fixtureCreator->getFixturesPath() . $this->gitSource->getPath()
//        );
//        $targetAbsolutePath = Path::canonicalize($this->fileStoreBasePath . '/' . $runSource);
//
//        self::assertSame(scandir($sourceAbsolutePath), scandir($targetAbsolutePath));
//    }

    public function testPrepareSuccess(): void
    {
        $this->setGitRepositoryClonerOutcome(new ProcessOutput(0, 'clone success output', ''));
        $this->setGitRepositoryCheckoutHandlerOutcome(new ProcessOutput(0, 'checkout output', ''));

        $runSource = $this->gitSourcePreparer->prepare($this->gitSource, self::REF);

        self::assertDirectoryDoesNotExist($this->repositoryPath);

        $sourceAbsolutePath = Path::canonicalize(
            $this->fixtureCreator->getFixturesPath() . $this->gitSource->getPath()
        );
        $targetAbsolutePath = Path::canonicalize($this->fileStoreBasePath . '/' . $runSource);

        self::assertSame(scandir($sourceAbsolutePath), scandir($targetAbsolutePath));
    }

    private function setGitRepositoryClonerOutcome(ProcessOutput|\Exception $outcome): void
    {
        ObjectReflector::setProperty(
            $this->gitSourcePreparer,
            GitSourcePreparer::class,
            'gitRepositoryCloner',
            $this->createGitRepositoryCloner($outcome)
        );
    }

    private function setGitRepositoryCheckoutHandlerOutcome(ProcessOutput|\Exception $outcome): void
    {
        ObjectReflector::setProperty(
            $this->gitSourcePreparer,
            GitSourcePreparer::class,
            'gitRepositoryCheckoutHandler',
            $this->createGitRepositoryCheckoutHandler($this->repositoryPath, $outcome)
        );
    }

    private function createCloneProcessErrorOutput(string $message): ProcessOutput
    {
        return new ProcessOutput(
            128,
            '',
            sprintf('Cloning into \'%s\'' . "\n" . '%s', self::REPOSITORY_URL, $message)
        );
    }

    private function createCloneMessageForRepositoryNotFound(): string
    {
        return sprintf('fatal: repository \'%s\' not found', self::REPOSITORY_URL);
    }

    private function doPrepareWithExpectedGitCloneExceptionTest(
        ProcessOutput|\Exception $gitCloneProcessOutcome,
        callable $assertions
    ): void {
        $this->setGitRepositoryClonerOutcome($gitCloneProcessOutcome);

        try {
            $this->gitSourcePreparer->prepare($this->gitSource, 'ref not relevant');
            self::fail(GitActionException::class . ' not thrown');
        } catch (GitActionException $gitCloneException) {
            $assertions($gitCloneProcessOutcome, $gitCloneException);
        }

        self::assertNotSame('', $this->repositoryPath);

        $fileStoreUserPath = substr($this->repositoryPath, 0, (int) strrpos($this->repositoryPath, '/'));
        self::assertDirectoryDoesNotExist($this->repositoryPath);
        self::assertDirectoryExists($fileStoreUserPath);
    }

    private function createGitRepositoryCloner(ProcessOutput|\Exception $outcome): GitRepositoryCloner
    {
        return (new MockGitRepositoryCloner())
            ->withCloneCall(self::REPOSITORY_URL, $outcome, $this->repositoryPath)
            ->getMock();
    }

    private function createGitRepositoryCheckoutHandler(
        string &$repositoryPath,
        \Exception|ProcessOutput $outcome
    ): GitRepositoryCheckoutHandler {
        if ($outcome instanceof ProcessOutput) {
            $checkoutOutcome = function (string $repositoryPath) use ($outcome): ProcessOutput {
                $repositoryRelativePath = (string) (new UnicodeString($repositoryPath))
                    ->trimPrefix($this->fileStoreBasePath . '/');
                $this->fixtureCreator->copyFixturesTo($repositoryRelativePath);

                return $outcome;
            };
        } else {
            $checkoutOutcome = $outcome;
        }

        return (new MockGitRepositoryCheckoutHandler())
            ->withCheckoutCall(
                $repositoryPath,
                self::REF,
                $checkoutOutcome
            )->getMock();
    }
}
