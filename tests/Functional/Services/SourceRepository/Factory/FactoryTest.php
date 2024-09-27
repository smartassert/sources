<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services\SourceRepository\Factory;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\SourceInterface;
use App\Enum\Source\Type;
use App\Exception\NoSourceRepositoryCreatorException;
use App\Model\SourceRepositoryInterface;
use App\Model\UserGitRepository;
use App\Services\EntityIdFactory;
use App\Services\SourceRepository\Factory\Factory;
use App\Services\SourceRepository\Factory\FileSourceHandler;
use App\Services\SourceRepository\Factory\GitSourceHandler;
use App\Tests\Services\FileStoreFixtureCreator;
use App\Tests\Services\SourceOriginFactory;
use App\Tests\Services\StringFactory;
use League\Flysystem\FilesystemOperator;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FactoryTest extends WebTestCase
{
    private Factory $factory;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $factory = self::getContainer()->get(Factory::class);
        \assert($factory instanceof Factory);
        $this->factory = $factory;
    }

    public function testHasFileSourceAndGitSourceHandlers(): void
    {
        $foo = new \ReflectionClass(Factory::class);
        $bar = $foo->getProperty('handlers');

        $handlers = $bar->getValue($this->factory);
        self::assertIsArray($handlers);

        self::assertInstanceOf(FileSourceHandler::class, $handlers[0]);
        self::assertInstanceOf(GitSourceHandler::class, $handlers[1]);
    }

    public function testCreateForUnknownSource(): void
    {
        $sourceId = StringFactory::createRandom();
        $sourceType = Type::FILE;

        $source = \Mockery::mock(SourceInterface::class);
        $source
            ->shouldReceive('getId')
            ->andReturn($sourceId)
        ;
        $source
            ->shouldReceive('getType')
            ->andReturn($sourceType)
        ;

        self::expectException(NoSourceRepositoryCreatorException::class);
        self::expectExceptionMessage(sprintf(
            'No source repository creator is available for source "%s" of type "%s"',
            $sourceId,
            $sourceType->value,
        ));

        $this->factory->create($source, []);
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

        $factory = new Factory([$gitSourceHandler]);

        self::assertSame($userGitRepository, $factory->create($gitSource, $parameters));
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
