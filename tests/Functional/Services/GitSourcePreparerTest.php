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
use App\Tests\Model\UserId;
use App\Tests\Services\FileStoreFixtureCreator;
use App\Tests\Services\Source\SourceRemover;
use PHPUnit\Framework\TestCase;
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
    private const CHECKOUT_ERROR = 'error: pathspec \'' . self::REF . '\' did not match any file(s) known to git';

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

    /**
     * @dataProvider prepareThrowsExceptionDataProvider
     *
     * @param callable(ProcessOutput|\Exception, null|ProcessOutput|\Exception, GitActionException): void $assertions
     */
    public function testPrepareThrowsException(
        ProcessOutput|\Exception $cloneProcessOutcome,
        null|ProcessOutput|\Exception $checkoutProcessOutcome,
        callable $assertions
    ): void {
        $this->setGitRepositoryClonerOutcome($cloneProcessOutcome);

        if (null != $checkoutProcessOutcome) {
            $this->setGitRepositoryCheckoutHandlerOutcome($checkoutProcessOutcome);
        }

        $assertionCount = self::getCount();

        try {
            $this->gitSourcePreparer->prepare($this->gitSource, self::REF);
            self::fail(GitActionException::class . ' not thrown');
        } catch (GitActionException $gitActionException) {
            $assertions($cloneProcessOutcome, $checkoutProcessOutcome, $gitActionException);
        }
        self::assertGreaterThan($assertionCount, self::getCount());

        self::assertNotSame('', $this->repositoryPath);

        $fileStoreUserPath = substr($this->repositoryPath, 0, (int) strrpos($this->repositoryPath, '/'));
        self::assertDirectoryDoesNotExist($this->repositoryPath);
        self::assertDirectoryExists($fileStoreUserPath);
    }

    /**
     * @return array<mixed>
     */
    public function prepareThrowsExceptionDataProvider(): array
    {
        return [
            'clone process throws exception' => [
                'cloneProcessOutcome' => GitActionException::createForProcessException(
                    GitActionException::ACTION_CLONE,
                    new ProcessExecutorException(
                        new SymfonyProcessRuntimeException('Unable to launch a new process.')
                    )
                ),
                'checkoutProcessOutcome' => null,
                'assertions' => function (
                    GitActionException $cloneProcessException,
                    ?object $checkoutProcessOutcome,
                    GitActionException $gitActionException
                ) {
                    self::assertSame(GitActionException::ACTION_CLONE, $gitActionException->getAction());
                    self::assertSame('Git clone process failed', $gitActionException->getMessage());
                    self::assertSame($cloneProcessException, $gitActionException);
                },
            ],
            'clone process fails to clone' => [
                'cloneProcessOutcome' => new ProcessOutput(
                    128,
                    '',
                    sprintf(
                        'Cloning into \'%s\'' . "\n" . 'fatal: repository \'%s\' not found',
                        self::REPOSITORY_URL,
                        self::REPOSITORY_URL
                    )
                ),
                'checkoutProcessOutcome' => null,
                'assertions' => function (
                    ProcessOutput $cloneProcessOutput,
                    ?object $checkoutProcessOutcome,
                    GitActionException $gitActionException
                ) {
                    self::assertSame(GitActionException::ACTION_CLONE, $gitActionException->getAction());
                    self::assertStringEndsWith(
                        $gitActionException->getMessage(),
                        $cloneProcessOutput->getErrorOutput()
                    );
                },
            ],
            'checkout process throws exception' => [
                'cloneProcessOutcome' => new ProcessOutput(0, 'clone success output', ''),
                'checkoutProcessOutcome' => GitActionException::createForProcessException(
                    GitActionException::ACTION_CHECKOUT,
                    new ProcessExecutorException(
                        new SymfonyProcessRuntimeException(sprintf(
                            'The provided cwd "%s" does not exist',
                            __DIR__ . '/does-not-exist'
                        ))
                    )
                ),
                'assertions' => function (
                    ProcessOutput $cloneProcessOutput,
                    GitActionException $passedCheckoutProcessException,
                    GitActionException $gitActionException
                ) {
                    self::assertSame(GitActionException::ACTION_CHECKOUT, $gitActionException->getAction());
                    self::assertSame('Git checkout process failed', $gitActionException->getMessage());
                    self::assertSame($passedCheckoutProcessException, $gitActionException);
                },
            ],
            'checkout process fails to checkout' => [
                'cloneProcessOutcome' => new ProcessOutput(0, 'clone success output', ''),
                'checkoutProcessOutcome' => new ProcessOutput(128, '', self::CHECKOUT_ERROR),
                'assertions' => function (
                    ProcessOutput $cloneProcessOutput,
                    ProcessOutput $passedCheckoutProcessOutput,
                    GitActionException $gitActionException
                ) {
                    self::assertSame(GitActionException::ACTION_CHECKOUT, $gitActionException->getAction());
                    self::assertSame(self::CHECKOUT_ERROR, $gitActionException->getMessage());
                },
            ],
        ];
    }

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
            $this->createGitRepositoryCheckoutHandler($outcome)
        );
    }

    private function createGitRepositoryCloner(ProcessOutput|\Exception $outcome): GitRepositoryCloner
    {
        $mock = \Mockery::mock(GitRepositoryCloner::class);
        $expectation = $mock
            ->shouldReceive('clone')
            ->withArgs(function (string $repositoryUrl, string $localPath): bool {
                TestCase::assertSame(self::REPOSITORY_URL, $repositoryUrl);
                $this->repositoryPath = $localPath;

                return true;
            })
        ;

        if ($outcome instanceof ProcessOutput) {
            $expectation->andReturn($outcome);
        } else {
            $expectation->andThrow($outcome);
        }

        return $mock;
    }

    private function createGitRepositoryCheckoutHandler(\Exception|ProcessOutput $outcome): GitRepositoryCheckoutHandler
    {
        $mock = \Mockery::mock(GitRepositoryCheckoutHandler::class);

        $expectation = $mock
            ->shouldReceive('checkout')
            ->withArgs(function (string $passedPath, string $passedRef) {
                TestCase::assertSame($this->repositoryPath, $passedPath);
                TestCase::assertSame(self::REF, $passedRef);

                return true;
            })
        ;

        if ($outcome instanceof ProcessOutput) {
            $expectation->andReturnUsing(function (string $repositoryPath) use ($outcome): ProcessOutput {
                $repositoryRelativePath =
                    (string) (new UnicodeString($repositoryPath))->trimPrefix($this->fileStoreBasePath . '/');
                $this->fixtureCreator->copyFixturesTo($repositoryRelativePath);

                return $outcome;
            });
        } else {
            $expectation->andThrow($outcome);
        }

        return $mock;
    }
}
