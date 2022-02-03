<?php

declare(strict_types=1);

namespace App\Tests\Functional\MessageHandler;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Enum\RunSource\State;
use App\Exception\DirectoryDuplicationException;
use App\Exception\MessageHandler\PrepareException;
use App\Exception\UserGitRepositoryException;
use App\Message\Prepare;
use App\MessageHandler\PrepareHandler;
use App\Model\EntityId;
use App\Repository\SourceRepository;
use App\Services\RunSourcePreparer;
use App\Services\Source\Store;
use App\Tests\Model\UserId;
use App\Tests\Services\EntityRemover;
use Doctrine\ORM\EntityManagerInterface;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use webignition\ObjectReflector\ObjectReflector;

class PrepareHandlerTest extends WebTestCase
{
    use MockeryPHPUnitIntegration;

    private PrepareHandler $handler;
    private EntityManagerInterface $entityManager;
    private SourceRepository $sourceRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $handler = self::getContainer()->get(PrepareHandler::class);
        \assert($handler instanceof PrepareHandler);
        $this->handler = $handler;

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        \assert($entityManager instanceof EntityManagerInterface);
        $this->entityManager = $entityManager;

        $sourceRepository = self::getContainer()->get(SourceRepository::class);
        \assert($sourceRepository instanceof SourceRepository);
        $this->sourceRepository = $sourceRepository;

        $entityRemover = self::getContainer()->get(EntityRemover::class);
        if ($entityRemover instanceof EntityRemover) {
            $entityRemover->removeAll();
        }
    }

    /**
     * @dataProvider invokeDoesNotPrepareDataProvider
     *
     * @param array<FileSource|GitSource|RunSource> $entities
     */
    public function testInvokeDoesNotPrepare(array $entities, Prepare $message): void
    {
        foreach ($entities as $entity) {
            $this->entityManager->persist($entity);
        }
        $this->entityManager->flush();

        ObjectReflector::setProperty(
            $this->handler,
            $this->handler::class,
            'sourceStore',
            \Mockery::mock(Store::class)
                ->shouldNotReceive('add')
                ->getMock()
        );

        ObjectReflector::setProperty(
            $this->handler,
            $this->handler::class,
            'runSourcePreparer',
            \Mockery::mock(RunSourcePreparer::class)
                ->shouldNotReceive('prepare')
                ->getMock()
        );

        $this->handler->__invoke($message);
    }

    /**
     * @return array<mixed>
     */
    public function invokeDoesNotPrepareDataProvider(): array
    {
        $fileSource = new FileSource(UserId::create(), 'file source label');
        $fileRunSource = new RunSource($fileSource, []);

        $gitSource = new GitSource(UserId::create(), 'http://example.com/repository.git');
        $gitRunSource = new RunSource($gitSource, []);

        $runSourceWithoutParent = $fileRunSource->unsetParent();

        return [
            'no entities' => [
                'entities' => [],
                'message' => new Prepare(EntityId::create(), []),
            ],
            'source is not RunSource' => [
                'entities' => [
                    $fileSource
                ],
                'message' => new Prepare($fileSource->getId(), []),
            ],
            'source has no parent' => [
                'entities' => [
                    $runSourceWithoutParent
                ],
                'message' => new Prepare($runSourceWithoutParent->getId(), []),
            ],
            'file run source preparation state is "preparing/running"' => [
                'entities' => [
                    $fileSource,
                    $fileRunSource->setState(State::PREPARING_RUNNING),
                ],
                'message' => new Prepare($fileRunSource->getId(), []),
            ],
            'file run source preparation state is "failed"' => [
                'entities' => [
                    $fileSource,
                    $fileRunSource->setState(State::FAILED),
                ],
                'message' => new Prepare($fileRunSource->getId(), []),
            ],
            'file run source preparation state is "prepared"' => [
                'entities' => [
                    $fileSource,
                    $fileRunSource->setState(State::PREPARED),
                ],
                'message' => new Prepare($fileRunSource->getId(), []),
            ],
            'git run source preparation state is "preparing/running"' => [
                'entities' => [
                    $gitSource,
                    $gitRunSource->setState(State::PREPARING_RUNNING),
                ],
                'message' => new Prepare($gitRunSource->getId(), []),
            ],
        ];
    }

    /**
     * @dataProvider invokeDoesPrepareDataProvider
     *
     * @param array<FileSource|GitSource|RunSource> $entities
     */
    public function testInvokeDoesPrepare(
        array $entities,
        RunSourcePreparer $runSourcePreparer,
        Prepare $message,
    ): void {
        foreach ($entities as $entity) {
            $this->entityManager->persist($entity);
        }
        $this->entityManager->flush();

        ObjectReflector::setProperty(
            $this->handler,
            $this->handler::class,
            'runSourcePreparer',
            $runSourcePreparer
        );

        $source = $this->sourceRepository->find($message->getSourceId());
        self::assertInstanceOf(RunSource::class, $source);
        self::assertNotSame(State::PREPARED, $source->getState());

        $this->handler->__invoke($message);

        self::assertSame(State::PREPARED, $source->getState());
    }

    /**
     * @return array<mixed>
     */
    public function invokeDoesPrepareDataProvider(): array
    {
        $fileSource = new FileSource(UserId::create(), 'file source label');
        $gitSource = new GitSource(UserId::create(), 'http://example.com/repository.git');

        $fileRunSource = new RunSource($fileSource);
        $gitRunSource = new RunSource($gitSource);
        $fileRunSourceStatePreparingHalted = (new RunSource($fileSource))->setState(State::PREPARING_HALTED);

        return [
            'run source is parent of file source, state is "requested"' => [
                'entities' => [
                    $fileSource,
                    $fileRunSource,
                ],
                'runSourcePreparer' => $this->createRunSourcePreparer($fileRunSource),
                'message' => Prepare::createFromRunSource($fileRunSource),
            ],
            'run source is parent of git source, state is "requested"' => [
                'entities' => [
                    $gitSource,
                    $gitRunSource,
                ],
                'runSourcePreparer' => $this->createRunSourcePreparer($gitRunSource),
                'message' => Prepare::createFromRunSource($gitRunSource),
            ],
            'run source is parent of file source, state is "preparing/halted"' => [
                'entities' => [
                    $fileSource,
                    $fileRunSourceStatePreparingHalted,
                ],
                'runSourcePreparer' => $this->createRunSourcePreparer($fileRunSourceStatePreparingHalted),
                'message' => Prepare::createFromRunSource($fileRunSourceStatePreparingHalted),
            ],
        ];
    }

    /**
     * @dataProvider invokeRunSourcePreparerThrowsExceptionDataProvider
     */
    public function testInvokeRunSourcePreparerThrowsException(\Exception $runSourcePreparerException): void
    {
        $fileRunSource = new RunSource(new FileSource(UserId::create(), 'file source label'));
        $this->entityManager->persist($fileRunSource);
        $this->entityManager->flush();

        $runSourcePreparer = $this->createRunSourcePreparer($fileRunSource, $runSourcePreparerException);

        ObjectReflector::setProperty(
            $this->handler,
            $this->handler::class,
            'runSourcePreparer',
            $runSourcePreparer
        );

        $message = Prepare::createFromRunSource($fileRunSource);

        $source = $this->sourceRepository->find($message->getSourceId());
        self::assertInstanceOf(RunSource::class, $source);
        self::assertNotSame(State::PREPARED, $source->getState());

        try {
            $this->handler->__invoke($message);
            self::fail('Prepare exception not thrown');
        } catch (PrepareException $prepareException) {
            self::assertSame($runSourcePreparerException, $prepareException->getHandlerException());
            self::assertSame(State::PREPARING_HALTED, $source->getState());
        }
    }

    /**
     * @return array<mixed>
     */
    public function invokeRunSourcePreparerThrowsExceptionDataProvider(): array
    {
        return [
            DirectoryDuplicationException::class => [
                'runSourcePreparerException' => \Mockery::mock(DirectoryDuplicationException::class),
            ],
            UserGitRepositoryException::class => [
                'runSourcePreparerException' => \Mockery::mock(UserGitRepositoryException::class),
            ],
        ];
    }

    private function createRunSourcePreparer(RunSource $runSource, ?\Exception $exception = null): RunSourcePreparer
    {
        $runSourcePreparer = \Mockery::mock(RunSourcePreparer::class);
        $expectation = $runSourcePreparer
            ->shouldReceive('prepare')
            ->withArgs(function (RunSource $passedRunSource) use ($runSource) {
                self::assertSame($runSource->getId(), $passedRunSource->getId());

                return true;
            })
        ;

        if ($exception instanceof \Exception) {
            $expectation->andThrow($exception);
        }

        return $runSourcePreparer;
    }
}
