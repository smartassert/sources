<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services\SerializableSource\Factory;

use App\Entity\GitSource;
use App\Exception\GitRepositoryException;
use App\Exception\SerializableSourceCreationException;
use App\Services\GitRepositoryStore;
use App\Services\SerializableSource\Factory\GitSourceHandler;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use webignition\ObjectReflector\ObjectReflector;

class GitSourceHandlerTest extends WebTestCase
{
    private GitSourceHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $handler = self::getContainer()->get(GitSourceHandler::class);
        \assert($handler instanceof GitSourceHandler);
        $this->handler = $handler;
    }

    public function testCreateThrowsSerializableSourceCreationException(): void
    {
        $gitRepositoryException = \Mockery::mock(GitRepositoryException::class);

        $source = \Mockery::mock(GitSource::class);

        $gitRepositoryStore = \Mockery::mock(GitRepositoryStore::class);
        $gitRepositoryStore
            ->shouldReceive('initialize')
            ->with($source, null)
            ->andThrow($gitRepositoryException)
        ;

        ObjectReflector::setProperty(
            $this->handler,
            $this->handler::class,
            'gitRepositoryStore',
            $gitRepositoryStore
        );

        try {
            $this->handler->create($source, []);
            self::fail(SerializableSourceCreationException::class . ' not thrown');
        } catch (SerializableSourceCreationException $exception) {
            self::assertSame($gitRepositoryException, $exception->getPrevious());
        }
    }
}
