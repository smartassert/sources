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
        $fileSource = new FileSource(md5((string) rand()), md5((string) rand()));
        $fileSourceSuite = new Suite(md5((string) rand()));
        $fileSourceSuite->setSource($fileSource);

        $fileSourceSerializedSuite = new SerializedSuite(md5((string) rand()), $fileSourceSuite, []);

        $gitSource = new GitSource(md5((string) rand()), md5((string) rand()));
        $gitSourcePath = md5((string) rand());
        $gitSource->setPath($gitSourcePath);

        $gitSourceSuite = new Suite(md5((string) rand()));
        $gitSourceSuite->setSource($gitSource);

        $gitSourceSerializedSuite = new SerializedSuite(md5((string) rand()), $gitSourceSuite, []);

        $noSourceRepositoryCreatorException = new NoSourceRepositoryCreatorException($fileSource);

        $pathTraversalDetectedPath = md5((string) rand());
        $pathTraversalDetectedException = PathTraversalDetected::forPath($pathTraversalDetectedPath);

        $unableToWriteSerializedSuiteException = new UnableToWriteSerializedSuiteException(
            md5((string) rand()),
            md5((string) rand()),
            new \Exception(),
        );

        $sourceRepository = \Mockery::mock(SourceRepositoryInterface::class);
        $sourceRepository
            ->shouldReceive('getRepositoryPath')
            ->andReturn(md5((string) rand()))
        ;

        $sourceRepositoryReaderNotFoundException = new SourceRepositoryReaderNotFoundException($sourceRepository);

        $gitRepositoryCloneException = new GitRepositoryException(
            new GitActionException(
                GitActionException::ACTION_CLONE,
                md5((string) rand())
            ),
        );

        $sourceRepositoryCreationGitCloneException = new SourceRepositoryCreationException(
            $gitRepositoryCloneException
        );

        $gitRepositoryCheckoutException = new GitRepositoryException(
            new GitActionException(
                GitActionException::ACTION_CHECKOUT,
                md5((string) rand())
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
