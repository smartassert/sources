<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Model\UserGitRepository;
use App\Services\RunSourcePreparer;
use App\Services\UserGitRepositoryPreparer;
use App\Tests\Model\UserId;
use App\Tests\Services\FileStoreFixtureCreator;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use webignition\ObjectReflector\ObjectReflector;

class RunSourcePreparerTest extends WebTestCase
{
    private RunSourcePreparer $runSourcePreparer;
    private FileStoreFixtureCreator $fixtureCreator;
    private string $fileStoreBasePath;

    protected function setUp(): void
    {
        parent::setUp();

        $runSourcePreparer = self::getContainer()->get(RunSourcePreparer::class);
        \assert($runSourcePreparer instanceof RunSourcePreparer);
        $this->runSourcePreparer = $runSourcePreparer;

        $fixtureCreator = self::getContainer()->get(FileStoreFixtureCreator::class);
        \assert($fixtureCreator instanceof FileStoreFixtureCreator);
        $this->fixtureCreator = $fixtureCreator;

        $fileStoreBasePath = self::getContainer()->getParameter('file_store_base_path');
        \assert(is_string($fileStoreBasePath));
        $this->fileStoreBasePath = $fileStoreBasePath;
    }

    public function testPrepareFileSourceSuccess(): void
    {
        $fileSource = new FileSource(UserId::create(), 'file source label');
        $this->fixtureCreator->copyFixtureSetTo('source_txt', $fileSource->getPath());

        $runSource = new RunSource($fileSource);

        $this->runSourcePreparer->prepare($runSource);

        $sourceAbsolutePath = $this->fileStoreBasePath . '/' . $fileSource;
        $targetAbsolutePath = $this->fileStoreBasePath . '/' . $runSource;

        self::assertSame(scandir($sourceAbsolutePath), scandir($targetAbsolutePath));
    }

    public function testPrepareGitSourceSuccess(): void
    {
        $ref = 'v1.1';

        $gitSource = new GitSource(UserId::create(), 'http://example.com/repository.git', '/directory');
        $userGitRepository = new UserGitRepository($gitSource);
        $repositoryPath = $this->fileStoreBasePath . '/' . $userGitRepository;

        $fixtureSet = 'source_txt';

        $gitRepositoryPreparer = \Mockery::mock(UserGitRepositoryPreparer::class);
        $gitRepositoryPreparer
            ->shouldReceive('prepare')
            ->withArgs(function (
                GitSource $passedGitSource,
                string $passedRef
            ) use (
                $gitSource,
                $ref,
                $userGitRepository,
                $fixtureSet
            ) {
                self::assertSame($gitSource, $passedGitSource);
                self::assertSame($ref, $passedRef);
                $this->fixtureCreator->copyFixtureSetTo($fixtureSet, (string) $userGitRepository);

                return true;
            })
            ->andReturn($userGitRepository)
        ;

        ObjectReflector::setProperty(
            $this->runSourcePreparer,
            $this->runSourcePreparer::class,
            'gitRepositoryPreparer',
            $gitRepositoryPreparer
        );

        $runSource = new RunSource($gitSource, ['ref' => $ref]);

        $this->runSourcePreparer->prepare($runSource);

        $sourceAbsolutePath = $this->fixtureCreator->getFixtureSetPath($fixtureSet) . $gitSource->getPath();
        $targetAbsolutePath = $this->fileStoreBasePath . '/' . $runSource;

        self::assertSame(scandir($sourceAbsolutePath), scandir($targetAbsolutePath));
        self::assertDirectoryDoesNotExist($repositoryPath);
    }
}
