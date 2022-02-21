<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\GitSource;
use App\Exception\GitActionException;
use App\Exception\GitRepositoryException;
use App\Exception\GitRepositoryException as RepositoryException;
use App\Exception\ProcessExecutorException;
use App\Exception\Storage\RemoveException;
use App\Model\ProcessOutput;
use App\Model\UserGitRepository;
use App\Services\FileStoreInterface;
use App\Services\FileStoreManager;
use App\Services\GitRepositoryCheckoutHandler;
use App\Services\GitRepositoryCloner;
use App\Services\GitRepositoryStore;
use App\Services\UserGitRepositoryFactory;
use App\Tests\Model\UserId;
use App\Tests\Services\EntityRemover;
use App\Tests\Services\FileStoreFixtureCreator;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToDeleteDirectory;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Process\Exception\RuntimeException as SymfonyProcessRuntimeException;
use webignition\ObjectReflector\ObjectReflector;

class GitRepositoryStoreTest extends WebTestCase
{
    private const REPOSITORY_URL = 'https://example.com/repository.git';
    private const REF = 'v1.1';
    private const PATH = '/directory';
    private const CHECKOUT_ERROR = 'error: pathspec \'' . self::REF . '\' did not match any file(s) known to git';

    private GitRepositoryStore $gitRepositoryStore;
    private FileStoreFixtureCreator $fixtureCreator;
    private GitSource $source;
    private UserGitRepository $gitRepository;
    private FilesystemOperator $gitRepositoryStorage;
    private FileStoreManager $gitRepositoryFileStore;
    private FileStoreManager $fixturesFileStore;
    private string $gitRepositoryAbsolutePath;

    protected function setUp(): void
    {
        parent::setUp();

        $gitRepositoryStore = self::getContainer()->get(GitRepositoryStore::class);
        \assert($gitRepositoryStore instanceof GitRepositoryStore);
        $this->gitRepositoryStore = $gitRepositoryStore;

        $fixtureCreator = self::getContainer()->get(FileStoreFixtureCreator::class);
        \assert($fixtureCreator instanceof FileStoreFixtureCreator);
        $this->fixtureCreator = $fixtureCreator;

        $gitRepositoryStorage = self::getContainer()->get('git_repository.storage');
        \assert($gitRepositoryStorage instanceof FilesystemOperator);
        $this->gitRepositoryStorage = $gitRepositoryStorage;

        $gitRepositoryFileStoreManager = self::getContainer()->get('app.services.file_store_manager.git_repository');
        \assert($gitRepositoryFileStoreManager instanceof FileStoreManager);
        $this->gitRepositoryFileStore = $gitRepositoryFileStoreManager;

        $fixturesFileStoreManager = self::getContainer()->get('app.tests.services.file_store_manager.fixtures');
        \assert($fixturesFileStoreManager instanceof FileStoreManager);
        $this->fixturesFileStore = $fixturesFileStoreManager;

        $entityRemover = self::getContainer()->get(EntityRemover::class);
        if ($entityRemover instanceof EntityRemover) {
            $entityRemover->removeAll();
        }

        $this->source = new GitSource(UserId::create(), self::REPOSITORY_URL, self::PATH);
        $this->gitRepository = new UserGitRepository($this->source);

        $gitRepositoryBasePath = self::getContainer()->getParameter('git_repository_store_directory');
        \assert(is_string($gitRepositoryBasePath));
        $this->gitRepositoryAbsolutePath = $gitRepositoryBasePath . '/' . $this->gitRepository;

        $this->setGitRepositoryStoreUserGitRepositoryFactory();
    }

    public function testInitializeUnableToRemoveExistingFileStore(): void
    {
        $unableToDeleteDirectoryException = UnableToDeleteDirectory::atLocation((string) $this->gitRepository);

        $removeException = new RemoveException(
            (string) $this->gitRepository,
            $unableToDeleteDirectoryException
        );

        $gitRepositoryFileStore = \Mockery::mock(FileStoreInterface::class);
        $gitRepositoryFileStore
            ->shouldReceive('remove')
            ->with((string) $this->gitRepository)
            ->andThrow($removeException)
        ;

        ObjectReflector::setProperty(
            $this->gitRepositoryStore,
            GitRepositoryStore::class,
            'gitRepositoryFileStore',
            $gitRepositoryFileStore
        );

        $this->expectExceptionObject(new GitRepositoryException($removeException));

        $this->gitRepositoryStore->initialize($this->source, 'ref value goes right here');
    }

    /**
     * @dataProvider initializeThrowsGitActionExceptionDataProvider
     *
     * @param callable(\Exception|ProcessOutput, null|\Exception|ProcessOutput, RepositoryException): void $assertions
     */
    public function testInitializeThrowsGitActionException(
        ProcessOutput|\Exception $cloneProcessOutcome,
        null|ProcessOutput|\Exception $checkoutProcessOutcome,
        callable $assertions
    ): void {
        $this->setGitRepositoryClonerOutcome($cloneProcessOutcome);

        if (null != $checkoutProcessOutcome) {
            $this->setGitRepositoryCheckoutHandlerOutcome($checkoutProcessOutcome);
        }

        $assertionCount = self::getCount();
        $userGitRepositoryException = null;

        try {
            $this->gitRepositoryStore->initialize($this->source, self::REF);
            self::fail(RepositoryException::class . ' not thrown');
        } catch (RepositoryException $userGitRepositoryException) {
            $assertions($cloneProcessOutcome, $checkoutProcessOutcome, $userGitRepositoryException);
        }

        self::assertGreaterThan($assertionCount, self::getCount());
        self::assertInstanceOf(RepositoryException::class, $userGitRepositoryException);
        self::assertFalse($this->gitRepositoryStorage->directoryExists((string) $this->gitRepository));
    }

    /**
     * @return array<mixed>
     */
    public function initializeThrowsGitActionExceptionDataProvider(): array
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

    public function testCreateSuccess(): void
    {
        $fixtureSetIdentifier = 'Source/txt';

        $this->setGitRepositoryClonerOutcome(new ProcessOutput(0, 'clone success output', ''));
        $this->setGitRepositoryCheckoutHandlerOutcome(
            new ProcessOutput(0, 'checkout output', ''),
            $fixtureSetIdentifier
        );

        $this->gitRepositoryStore->initialize($this->source, self::REF);

        $userGitRepositoryPath = (string) $this->gitRepository;

        self::assertTrue($this->gitRepositoryStorage->directoryExists($userGitRepositoryPath));
        self::assertSame(
            $this->fixturesFileStore->list($fixtureSetIdentifier),
            $this->gitRepositoryFileStore->list($userGitRepositoryPath)
        );
    }

    private function setGitRepositoryClonerOutcome(ProcessOutput|\Exception $outcome): void
    {
        ObjectReflector::setProperty(
            $this->gitRepositoryStore,
            GitRepositoryStore::class,
            'cloner',
            $this->createGitRepositoryCloner($outcome)
        );
    }

    private function setGitRepositoryCheckoutHandlerOutcome(
        ProcessOutput|\Exception $outcome,
        ?string $fixtureSetIdentifier = null,
    ): void {
        ObjectReflector::setProperty(
            $this->gitRepositoryStore,
            GitRepositoryStore::class,
            'checkoutHandler',
            $this->createGitRepositoryCheckoutHandler($outcome, $fixtureSetIdentifier)
        );
    }

    private function createGitRepositoryCloner(
        ProcessOutput|\Exception $outcome
    ): GitRepositoryCloner {
        $mock = \Mockery::mock(GitRepositoryCloner::class);
        $expectation = $mock
            ->shouldReceive('clone')
            ->withArgs(function (string $repositoryUrl, string $localPath): bool {
                TestCase::assertSame(self::REPOSITORY_URL, $repositoryUrl);
                TestCase::assertSame($this->gitRepositoryAbsolutePath, $localPath);

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
                TestCase::assertSame(self::REF, $passedRef);

                return true;
            })
        ;

        if ($outcome instanceof ProcessOutput) {
            $expectation->andReturnUsing(
                function (string $repositoryPath) use ($outcome, $fixtureSetIdentifier): ProcessOutput {
                    if (
                        $outcome instanceof ProcessOutput
                        && $outcome->isSuccessful()
                        && is_string($fixtureSetIdentifier)
                    ) {
                        $this->fixtureCreator->copySetTo(
                            $fixtureSetIdentifier,
                            $this->gitRepositoryStorage,
                            $this->getRepositoryRelativePath($repositoryPath)
                        );
                    }

                    return $outcome;
                }
            );
        } else {
            $expectation->andThrow($outcome);
        }

        return $mock;
    }

    private function getRepositoryRelativePath(string $path): string
    {
        $pathParts = explode('/', $path);
        $relativePathParts = array_slice($pathParts, -2);

        return implode('/', $relativePathParts);
    }

    private function setGitRepositoryStoreUserGitRepositoryFactory(): void
    {
        $factory = \Mockery::mock(UserGitRepositoryFactory::class);
        $factory
            ->shouldReceive('create')
            ->with($this->source)
            ->andReturn($this->gitRepository)
        ;

        ObjectReflector::setProperty(
            $this->gitRepositoryStore,
            GitRepositoryStore::class,
            'gitRepositoryFactory',
            $factory
        );
    }
}
