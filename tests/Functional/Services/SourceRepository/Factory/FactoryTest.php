<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services\SourceRepository\Factory;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\SourceInterface;
use App\Model\SourceRepositoryInterface;
use App\Model\UserGitRepository;
use App\Services\EntityIdFactory;
use App\Services\SourceRepository\Factory\Factory;
use App\Services\SourceRepository\Factory\GitSourceHandler;
use App\Tests\Services\FileStoreFixtureCreator;
use App\Tests\Services\SourceOriginFactory;
use League\Flysystem\FilesystemOperator;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use webignition\ObjectReflector\ObjectReflector;

class FactoryTest extends WebTestCase
{
    private Factory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $factory = self::getContainer()->get(Factory::class);
        \assert($factory instanceof Factory);
        $this->factory = $factory;
    }

    /**
     * @dataProvider createsForDataProvider
     */
    public function testCreatesFor(SourceInterface $source, bool $expected): void
    {
        self::assertSame($expected, $this->factory->createsFor($source));
    }

    /**
     * @return array<mixed>
     */
    public function createsForDataProvider(): array
    {
        return [
            FileSource::class => [
                'source' => \Mockery::mock(FileSource::class),
                'expected' => true,
            ],
            GitSource::class => [
                'source' => \Mockery::mock(GitSource::class),
                'expected' => true,
            ],
            SourceInterface::class => [
                'source' => \Mockery::mock(SourceInterface::class),
                'expected' => false,
            ],
        ];
    }

    /**
     * @dataProvider removesDataProvider
     */
    public function testRemoves(SourceRepositoryInterface $source, bool $expected): void
    {
        self::assertSame($expected, $this->factory->removes($source));
    }

    /**
     * @return array<mixed>
     */
    public function removesDataProvider(): array
    {
        return [
            FileSource::class => [
                'source' => \Mockery::mock(FileSource::class),
                'expected' => false,
            ],
            UserGitRepository::class => [
                'source' => \Mockery::mock(UserGitRepository::class),
                'expected' => true,
            ],
            SourceRepositoryInterface::class => [
                'source' => \Mockery::mock(SourceRepositoryInterface::class),
                'expected' => false,
            ],
        ];
    }

    public function testCreateForUnknownSource(): void
    {
        self::assertNull(
            $this->factory->create(\Mockery::mock(SourceInterface::class), [])
        );
    }

    public function testCreateForFileSource(): void
    {
        $fileSource = SourceOriginFactory::create(type: 'file');

        self::assertSame($fileSource, $this->factory->create($fileSource, []));
    }

    public function testCreateForGitSourceSuccess(): void
    {
        $gitSource = SourceOriginFactory::create(type: 'git');
        \assert($gitSource instanceof GitSource);

        $parameters = ['ref' => 'v1.1'];
        $userGitRepository = new UserGitRepository((new EntityIdFactory())->create(), $gitSource);

        $gitSourceHandler = self::getContainer()->get(GitSourceHandler::class);
        \assert($gitSourceHandler instanceof GitSourceHandler);

        $gitSourceHandler = \Mockery::mock($gitSourceHandler);
        $gitSourceHandler
            ->shouldReceive('create')
            ->with($gitSource, $parameters)
            ->andReturn($userGitRepository)
        ;

        $factoryHandlers = ObjectReflector::getProperty(
            $this->factory,
            'handlers'
        );
        \assert(is_array($factoryHandlers));
        \assert($factoryHandlers[1] instanceof GitSourceHandler);

        $factoryHandlers[1] = $gitSourceHandler;

        ObjectReflector::setProperty(
            $this->factory,
            $this->factory::class,
            'handlers',
            $factoryHandlers
        );

        self::assertSame($userGitRepository, $this->factory->create($gitSource, $parameters));
    }

    public function testRemoveForUnknownSource(): void
    {
        self::expectNotToPerformAssertions();

        $this->factory->remove(\Mockery::mock(SourceRepositoryInterface::class));
    }

    public function testRemoveForFileSource(): void
    {
        self::expectNotToPerformAssertions();

        $this->factory->remove(\Mockery::mock(FileSource::class));
    }

    public function testRemoveForUserGitRepository(): void
    {
        $fixtureCreator = self::getContainer()->get(FileStoreFixtureCreator::class);
        \assert($fixtureCreator instanceof FileStoreFixtureCreator);

        $gitRepositoryStorage = self::getContainer()->get('git_repository.storage');
        \assert($gitRepositoryStorage instanceof FilesystemOperator);

        $source = SourceOriginFactory::create(type: 'git');
        \assert($source instanceof GitSource);

        $userGitRepository = new UserGitRepository((new EntityIdFactory())->create(), $source);

        $fixtureCreator->copySetTo('Source/mixed', $gitRepositoryStorage, $userGitRepository->getDirectoryPath());
        self::assertTrue($gitRepositoryStorage->directoryExists($userGitRepository->getDirectoryPath()));

        $this->factory->remove($userGitRepository);
        self::assertFalse($gitRepositoryStorage->directoryExists($userGitRepository->getDirectoryPath()));
    }
}
