<?php

declare(strict_types=1);

namespace App\Tests\Functional\MessageHandler;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Enum\RunSource\State;
use App\Message\Prepare;
use App\MessageHandler\PrepareHandler;
use App\Model\EntityId;
use App\Repository\RunSourceRepository;
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
    private RunSourceRepository $runSourceRepository;

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

        $runSourceRepository = self::getContainer()->get(RunSourceRepository::class);
        \assert($runSourceRepository instanceof RunSourceRepository);
        $this->runSourceRepository = $runSourceRepository;

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

//        $fileRunSourcePreparationStateUnknown = new RunSourcePreparation($fileRunSource);
//        $fileRunSourcePreparationStateUnknown->setState(State::UNKNOWN);
//
//        $gitRunSourcePreparationStateUnknown = new RunSourcePreparation($gitRunSource);
//        $gitRunSourcePreparationStateUnknown->setState(State::UNKNOWN);
//
//        $runSourcePreparationStatePreparingRunning = new RunSourcePreparation($fileRunSource);
//        $runSourcePreparationStatePreparingRunning->setState(State::PREPARING_RUNNING);
//
//        $runSourcePreparationStateFailed = new RunSourcePreparation($fileRunSource);
//        $runSourcePreparationStateFailed->setState(State::FAILED);
//
//        $runSourcePreparationStatePrepared = new RunSourcePreparation($fileRunSource);
//        $runSourcePreparationStatePrepared->setState(State::PREPARED);

        return [
            'no entities' => [
                'entities' => [],
                'message' => new Prepare(EntityId::create(), []),
            ],
            'run source matches message source id' => [
                'entities' => [
                    $fileRunSource,
                ],
                'message' => new Prepare($fileRunSource->getId(), []),
            ],
            'file run source preparation state is "preparing/running"' => [
                'entities' => [
                    $fileSource,
                    $fileRunSource->setState(State::PREPARING_RUNNING),
                ],
                'message' => new Prepare($fileSource->getId(), []),
            ],
            'file run source preparation state is "failed"' => [
                'entities' => [
                    $fileSource,
                    $fileRunSource->setState(State::FAILED),
                ],
                'message' => new Prepare($fileSource->getId(), []),
            ],
            'file run source preparation state is "prepared"' => [
                'entities' => [
                    $fileSource,
                    $fileRunSource->setState(State::PREPARED),
                ],
                'message' => new Prepare($fileSource->getId(), []),
            ],
            'git run source preparation state is "preparing/running"' => [
                'entities' => [
                    $gitSource,
                    $gitRunSource->setState(State::PREPARING_RUNNING),
                ],
                'message' => new Prepare($gitSource->getId(), []),
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
        foreach ($entities as $source) {
            $this->entityManager->persist($source);
        }
        $this->entityManager->flush();

        ObjectReflector::setProperty(
            $this->handler,
            $this->handler::class,
            'runSourcePreparer',
            $runSourcePreparer
        );

        $this->handler->__invoke($message);

        $expectedSource = $this->sourceRepository->find($message->getSourceId());
        if (!($expectedSource instanceof FileSource || $expectedSource instanceof GitSource)) {
            self::fail('Expected source is not ' . FileSource::class . ' or ' . GitSource::class);
        }

        $runSource = $this->runSourceRepository->findByParent($expectedSource);
        self::assertInstanceOf(RunSource::class, $runSource);
        self::assertSame($expectedSource, $runSource->getParent());
    }

    /**
     * @return array<mixed>
     */
    public function invokeDoesPrepareDataProvider(): array
    {
        $fileSource = new FileSource(UserId::create(), 'file source label');
        $gitSource = new GitSource(UserId::create(), 'http://example.com/repository.git');

        $fileRunSourceStateRequested = new RunSource($fileSource);
        $fileRunSourceStatePreparingHalted = (new RunSource($fileSource))->setState(State::PREPARING_HALTED);

        return [
            'file source has no run source' => [
                'entities' => [
                    $fileSource,
                ],
                'runSourcePreparer' => $this->createRunSourcePreparer($fileSource),
                'message' => new Prepare($fileSource->getId(), []),
            ],
            'git source has no run source' => [
                'entities' => [
                    $gitSource,
                ],
                'runSourcePreparer' => $this->createRunSourcePreparer($gitSource),
                'message' => new Prepare($gitSource->getId(), []),
            ],
            'file source has run source, state is "requested"' => [
                'entities' => [
                    $fileSource,
                    $fileRunSourceStateRequested,
                ],
                'runSourcePreparer' => $this->createRunSourcePreparer($fileSource),
                'message' => new Prepare($fileSource->getId(), []),
            ],
            'file source has run source, state is "preparing/halted"' => [
                'entities' => [
                    $fileSource,
                    $fileRunSourceStatePreparingHalted,
                ],
                'runSourcePreparer' => $this->createRunSourcePreparer($fileSource),
                'message' => new Prepare($fileSource->getId(), []),
            ],
        ];
    }

    private function createRunSourcePreparer(FileSource|GitSource $source): RunSourcePreparer
    {
        $runSourcePreparer = \Mockery::mock(RunSourcePreparer::class);
        $runSourcePreparer
            ->shouldReceive('prepare')
            ->withArgs(function (RunSource $runSource) use ($source) {
                self::assertSame($source, $runSource->getParent());

                return true;
            })
        ;

        return $runSourcePreparer;
    }
}
