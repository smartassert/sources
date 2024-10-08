<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services\SerializeSuiteExceptionFactory;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\SerializedSuite;
use App\Entity\Suite;
use App\Enum\SerializedSuite\FailureReason;
use App\Exception\GitActionException;
use App\Exception\GitRepositoryException;
use App\Exception\MessageHandler\SerializeSuiteException;
use App\Exception\NoSourceRepositoryCreatorException;
use App\Exception\SourceRepositoryCreationException;
use App\Exception\SourceRepositoryReaderNotFoundException;
use App\Exception\UnableToWriteSerializedSuiteException;
use App\Model\SourceRepositoryInterface;
use App\Services\SerializeSuiteExceptionFactory\SerializeSuiteExceptionFactory;
use App\Tests\Services\StringFactory;
use League\Flysystem\PathTraversalDetected;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SerializeSuiteExceptionFactoryTest extends WebTestCase
{
    private SerializeSuiteExceptionFactory $serializeSuiteExceptionFactory;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $serializeSuiteExceptionFactory = self::getContainer()->get(SerializeSuiteExceptionFactory::class);
        \assert($serializeSuiteExceptionFactory instanceof SerializeSuiteExceptionFactory);
        $this->serializeSuiteExceptionFactory = $serializeSuiteExceptionFactory;
    }

    #[DataProvider('createDataProvider')]
    public function testCreate(
        SerializedSuite $serializedSuite,
        \Throwable $throwable,
        SerializeSuiteException $expected
    ): void {
        self::assertEquals($expected, $this->serializeSuiteExceptionFactory->create($serializedSuite, $throwable));
    }

    /**
     * @return array<mixed>
     */
    public static function createDataProvider(): array
    {
        $fileSource = new FileSource(StringFactory::createRandom(), StringFactory::createRandom());
        $fileSourceSuite = new Suite(StringFactory::createRandom());
        $fileSourceSuite->setSource($fileSource);

        $fileSourceSerializedSuite = new SerializedSuite(StringFactory::createRandom(), $fileSourceSuite, []);

        $gitSource = new GitSource(StringFactory::createRandom(), StringFactory::createRandom());
        $gitSourcePath = StringFactory::createRandom();
        $gitSource->setPath($gitSourcePath);

        $gitSourceSuite = new Suite(StringFactory::createRandom());
        $gitSourceSuite->setSource($gitSource);

        $gitSourceSerializedSuite = new SerializedSuite(StringFactory::createRandom(), $gitSourceSuite, []);

        $noSourceRepositoryCreatorException = new NoSourceRepositoryCreatorException($fileSource);

        $pathTraversalDetectedPath = StringFactory::createRandom();
        $pathTraversalDetectedException = PathTraversalDetected::forPath($pathTraversalDetectedPath);

        $unableToWriteSerializedSuiteException = new UnableToWriteSerializedSuiteException(
            StringFactory::createRandom(),
            StringFactory::createRandom(),
            new \Exception(),
        );

        $sourceRepository = \Mockery::mock(SourceRepositoryInterface::class);
        $sourceRepository
            ->shouldReceive('getRepositoryPath')
            ->andReturn(StringFactory::createRandom())
        ;

        $sourceRepositoryReaderNotFoundException = new SourceRepositoryReaderNotFoundException($sourceRepository);

        $gitRepositoryCloneException = new GitRepositoryException(
            new GitActionException(
                GitActionException::ACTION_CLONE,
                StringFactory::createRandom()
            ),
        );

        $sourceRepositoryCreationGitCloneException = new SourceRepositoryCreationException(
            $gitRepositoryCloneException
        );

        $gitRepositoryCheckoutException = new GitRepositoryException(
            new GitActionException(
                GitActionException::ACTION_CHECKOUT,
                StringFactory::createRandom()
            ),
        );

        $sourceRepositoryCreationGitCheckoutException = new SourceRepositoryCreationException(
            $gitRepositoryCheckoutException
        );

        return [
            NoSourceRepositoryCreatorException::class => [
                'serializedSuite' => $fileSourceSerializedSuite,
                'throwable' => new NoSourceRepositoryCreatorException($fileSource),
                'expected' => new SerializeSuiteException(
                    $fileSourceSerializedSuite,
                    $noSourceRepositoryCreatorException,
                    FailureReason::UNSERIALIZABLE_SOURCE_TYPE,
                    $fileSource->getType()->value,
                ),
            ],
            PathTraversalDetected::class => [
                'serializedSuite' => $gitSourceSerializedSuite,
                'throwable' => $pathTraversalDetectedException,
                'expected' => new SerializeSuiteException(
                    $gitSourceSerializedSuite,
                    $pathTraversalDetectedException,
                    FailureReason::GIT_REPOSITORY_OUT_OF_SCOPE,
                    $gitSourcePath,
                ),
            ],
            UnableToWriteSerializedSuiteException::class => [
                'serializedSuite' => $gitSourceSerializedSuite,
                'throwable' => $unableToWriteSerializedSuiteException,
                'expected' => new SerializeSuiteException(
                    $gitSourceSerializedSuite,
                    $unableToWriteSerializedSuiteException,
                    FailureReason::UNABLE_TO_WRITE_TO_TARGET,
                    $unableToWriteSerializedSuiteException->path,
                ),
            ],
            SourceRepositoryReaderNotFoundException::class => [
                'serializedSuite' => $gitSourceSerializedSuite,
                'throwable' => $sourceRepositoryReaderNotFoundException,
                'expected' => new SerializeSuiteException(
                    $gitSourceSerializedSuite,
                    $sourceRepositoryReaderNotFoundException,
                    FailureReason::UNABLE_TO_READ_FROM_SOURCE_REPOSITORY,
                    $sourceRepositoryReaderNotFoundException->source->getRepositoryPath(),
                ),
            ],
            SourceRepositoryCreationException::class . ':' . FailureReason::GIT_CLONE->value => [
                'serializedSuite' => $gitSourceSerializedSuite,
                'throwable' => $sourceRepositoryCreationGitCloneException,
                'expected' => new SerializeSuiteException(
                    $gitSourceSerializedSuite,
                    $sourceRepositoryCreationGitCloneException,
                    FailureReason::GIT_CLONE,
                    $gitRepositoryCloneException->getMessage(),
                ),
            ],
            SourceRepositoryCreationException::class . ':' . FailureReason::GIT_CHECKOUT->value => [
                'serializedSuite' => $gitSourceSerializedSuite,
                'throwable' => $sourceRepositoryCreationGitCheckoutException,
                'expected' => new SerializeSuiteException(
                    $gitSourceSerializedSuite,
                    $sourceRepositoryCreationGitCheckoutException,
                    FailureReason::GIT_CHECKOUT,
                    $gitRepositoryCheckoutException->getMessage(),
                ),
            ],
        ];
    }
}
