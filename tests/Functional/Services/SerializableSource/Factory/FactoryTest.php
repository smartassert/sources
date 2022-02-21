<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services\SerializableSource\Factory;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\OriginSourceInterface;
use App\Model\SerializableSourceInterface;
use App\Model\UserGitRepository;
use App\Services\SerializableSource\Factory\Factory;
use App\Services\SerializableSource\Factory\GitSourceHandler;
use App\Tests\Model\UserId;
use App\Tests\Services\FileStoreFixtureCreator;
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
    public function testCreatesFor(OriginSourceInterface $source, bool $expected): void
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
            OriginSourceInterface::class => [
                'source' => \Mockery::mock(OriginSourceInterface::class),
                'expected' => false,
            ],
        ];
    }

    /**
     * @dataProvider removesDataProvider
     */
    public function testRemoves(SerializableSourceInterface $source, bool $expected): void
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
            SerializableSourceInterface::class => [
                'source' => \Mockery::mock(SerializableSourceInterface::class),
                'expected' => false,
            ],
        ];
    }

    public function testCreateForUnknownSource(): void
    {
        self::assertNull(
            $this->factory->create(\Mockery::mock(OriginSourceInterface::class), [])
        );
    }

    public function testCreateForFileSource(): void
    {
        $fileSource = new FileSource(UserId::create(), 'file source label');

        self::assertSame($fileSource, $this->factory->create($fileSource, []));
    }

    public function testCreateForGitSourceSuccess(): void
    {
        $gitSource = new GitSource(UserId::create(), 'https://example.com/repository.git', '/');
        $parameters = ['ref' => 'v1.1'];
        $userGitRepository = new UserGitRepository($gitSource);

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

        $this->factory->remove(\Mockery::mock(SerializableSourceInterface::class));
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

        $userGitRepository = new UserGitRepository(
            new GitSource(UserId::create(), 'https://example.com/repository.git')
        );

        $fixtureCreator->copySetTo('Source/mixed', $gitRepositoryStorage, (string) $userGitRepository);
        self::assertTrue($gitRepositoryStorage->directoryExists((string) $userGitRepository));

        $this->factory->remove($userGitRepository);
        self::assertFalse($gitRepositoryStorage->directoryExists((string) $userGitRepository));
    }
}
