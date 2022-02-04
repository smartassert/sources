<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\GitSource;
use App\Exception\GitActionException;
use App\Exception\ProcessExecutorException;
use App\Exception\UserGitRepositoryException as RepositoryException;
use App\Model\ProcessOutput;
use App\Services\GitRepositoryCheckoutHandler;
use App\Services\GitRepositoryCloner;
use App\Services\UserGitRepositoryPreparer;
use App\Tests\Model\UserId;
use App\Tests\Services\EntityRemover;
use App\Tests\Services\FileStoreFixtureCreator;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Process\Exception\RuntimeException as SymfonyProcessRuntimeException;
use Symfony\Component\String\UnicodeString;
use webignition\ObjectReflector\ObjectReflector;

class UserGitRepositoryPreparerTest extends WebTestCase
{
    private const REPOSITORY_URL = 'https://example.com/repository.git';
    private const REF = 'v1.1';
    private const PATH = '/directory';
    private const CHECKOUT_ERROR = 'error: pathspec \'' . self::REF . '\' did not match any file(s) known to git';

    private UserGitRepositoryPreparer $userGitRepositoryPreparer;
    private FileStoreFixtureCreator $fixtureCreator;
    private string $fileStoreBasePath;
    private GitSource $gitSource;
    private string $repositoryPath = '';

    protected function setUp(): void
    {
        parent::setUp();

        $userGitRepositoryPreparer = self::getContainer()->get(UserGitRepositoryPreparer::class);
        \assert($userGitRepositoryPreparer instanceof UserGitRepositoryPreparer);
        $this->userGitRepositoryPreparer = $userGitRepositoryPreparer;

        $fixtureCreator = self::getContainer()->get(FileStoreFixtureCreator::class);
        \assert($fixtureCreator instanceof FileStoreFixtureCreator);
        $this->fixtureCreator = $fixtureCreator;

        $fileStoreBasePath = self::getContainer()->getParameter('file_store_base_path');
        \assert(is_string($fileStoreBasePath));
        $this->fileStoreBasePath = $fileStoreBasePath;

        $entityRemover = self::getContainer()->get(EntityRemover::class);
        if ($entityRemover instanceof EntityRemover) {
            $entityRemover->removeAll();
        }

        $this->gitSource = new GitSource(UserId::create(), self::REPOSITORY_URL, self::PATH);
    }

    /**
     * @dataProvider prepareThrowsExceptionDataProvider
     *
     * @param callable(ProcessOutput|\Exception, null|ProcessOutput|\Exception, RepositoryException): void $assertions
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
            $this->userGitRepositoryPreparer->prepare($this->gitSource, self::REF);
            self::fail(GitActionException::class . ' not thrown');
        } catch (RepositoryException $userGitRepositoryException) {
            $assertions($cloneProcessOutcome, $checkoutProcessOutcome, $userGitRepositoryException);
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
                    GitActionException $cloneOutcome,
                    ?object $checkoutOutcome,
                    RepositoryException $exception
                ) {
                    self::assertSame('Git clone process failed', $exception->getMessage());
                    self::assertSame($cloneOutcome, $exception->getPrevious());
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
                    ProcessOutput $cloneOutcome,
                    ?object $checkoutOutcome,
                    RepositoryException $exception
                ) {
                    self::assertStringEndsWith($exception->getMessage(), $cloneOutcome->getErrorOutput());

                    $gitActionException = $exception->getPrevious();
                    self::assertInstanceOf(GitActionException::class, $gitActionException);
                    self::assertSame(GitActionException::ACTION_CLONE, $gitActionException->getAction());
                    self::assertStringEndsWith($gitActionException->getMessage(), $cloneOutcome->getErrorOutput());
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
                    ProcessOutput $cloneOutcome,
                    GitActionException $checkoutOutcome,
                    RepositoryException $exception
                ) {
                    self::assertSame('Git checkout process failed', $exception->getMessage());
                    self::assertSame($checkoutOutcome, $exception->getPrevious());
                },
            ],
            'checkout process fails to checkout' => [
                'cloneProcessOutcome' => new ProcessOutput(0, 'clone success output', ''),
                'checkoutProcessOutcome' => new ProcessOutput(128, '', self::CHECKOUT_ERROR),
                'assertions' => function (
                    ProcessOutput $cloneOutcome,
                    ProcessOutput $checkoutOutcome,
                    RepositoryException $exception
                ) {
                    self::assertSame(self::CHECKOUT_ERROR, $exception->getMessage());

                    $gitActionException = $exception->getPrevious();
                    self::assertInstanceOf(GitActionException::class, $gitActionException);
                    self::assertSame(GitActionException::ACTION_CHECKOUT, $gitActionException->getAction());
                    self::assertSame(self::CHECKOUT_ERROR, $gitActionException->getMessage());
                },
            ],
        ];
    }

    public function testPrepareSuccess(): void
    {
        $fixtureSetIdentifier = 'txt';

        $this->setGitRepositoryClonerOutcome(new ProcessOutput(0, 'clone success output', ''));
        $this->setGitRepositoryCheckoutHandlerOutcome(
            new ProcessOutput(0, 'checkout output', ''),
            $fixtureSetIdentifier
        );

        $runSource = $this->userGitRepositoryPreparer->prepare($this->gitSource, self::REF);

        self::assertDirectoryExists($this->repositoryPath);

        $sourceAbsolutePath = Path::canonicalize($this->fixtureCreator->getFixtureSetPath($fixtureSetIdentifier));
        $targetAbsolutePath = Path::canonicalize($this->fileStoreBasePath . '/' . $runSource);

        self::assertSame(scandir($sourceAbsolutePath), scandir($targetAbsolutePath));
    }

    private function setGitRepositoryClonerOutcome(ProcessOutput|\Exception $outcome): void
    {
        ObjectReflector::setProperty(
            $this->userGitRepositoryPreparer,
            UserGitRepositoryPreparer::class,
            'gitRepositoryCloner',
            $this->createGitRepositoryCloner($outcome)
        );
    }

    private function setGitRepositoryCheckoutHandlerOutcome(
        ProcessOutput|\Exception $outcome,
        ?string $fixtureSetIdentifier = null,
    ): void {
        ObjectReflector::setProperty(
            $this->userGitRepositoryPreparer,
            UserGitRepositoryPreparer::class,
            'gitRepositoryCheckoutHandler',
            $this->createGitRepositoryCheckoutHandler($outcome, $fixtureSetIdentifier)
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

    private function createGitRepositoryCheckoutHandler(
        \Exception|ProcessOutput $outcome,
        ?string $fixtureSetIdentifier = null,
    ): GitRepositoryCheckoutHandler {
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
            $expectation->andReturnUsing(
                function (string $repositoryPath) use ($outcome, $fixtureSetIdentifier): ProcessOutput {
                    if (is_string($fixtureSetIdentifier)) {
                        $repositoryRelativePath = (string) (new UnicodeString($repositoryPath))
                            ->trimPrefix($this->fileStoreBasePath . '/')
                        ;
                        $this->fixtureCreator->copyFixtureSetTo($fixtureSetIdentifier, $repositoryRelativePath);
                    }

                    return $outcome;
                }
            );
        } else {
            $expectation->andThrow($outcome);
        }

        return $mock;
    }
}
