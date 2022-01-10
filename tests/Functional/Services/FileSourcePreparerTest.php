<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\FileSource;
use App\Exception\FilePath\NotExistsException;
use App\Exception\FileSourcePreparationException;
use App\Repository\SourceRepository;
use App\Services\FileSourcePreparer;
use App\Services\FileStoreManager;
use App\Tests\Mock\Services\MockFileStoreManager;
use App\Tests\Model\UserId;
use App\Tests\Services\FileStoreFixtureCreator;
use App\Tests\Services\Source\SourceRemover;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use webignition\ObjectReflector\ObjectReflector;

class FileSourcePreparerTest extends WebTestCase
{
    private FileSourcePreparer $fileSourcePreparer;
    private SourceRepository $sourceRepository;
    private FileStoreFixtureCreator $fixtureCreator;
    private FileStoreManager $fileStoreManager;

    protected function setUp(): void
    {
        parent::setUp();

        $fileSourcePreparer = self::getContainer()->get(FileSourcePreparer::class);
        \assert($fileSourcePreparer instanceof FileSourcePreparer);
        $this->fileSourcePreparer = $fileSourcePreparer;

        $sourceRepository = self::getContainer()->get(SourceRepository::class);
        \assert($sourceRepository instanceof SourceRepository);
        $this->sourceRepository = $sourceRepository;

        $fixtureCreator = self::getContainer()->get(FileStoreFixtureCreator::class);
        \assert($fixtureCreator instanceof FileStoreFixtureCreator);
        $this->fixtureCreator = $fixtureCreator;

        $fileStoreManager = self::getContainer()->get(FileStoreManager::class);
        \assert($fileStoreManager instanceof FileStoreManager);
        $this->fileStoreManager = $fileStoreManager;

        $sourceRemover = self::getContainer()->get(SourceRemover::class);
        if ($sourceRemover instanceof SourceRemover) {
            $sourceRemover->removeAll();
        }
    }

    public function testPrepareFileSourceMirrorException(): void
    {
        $source = new FileSource(UserId::create(), 'file source label');
        $exception = new NotExistsException('path-does-not-exist');

        $fileStoreManager = (new MockFileStoreManager())
            ->withMirrorCallThrowingException($exception)
            ->getMock()
        ;

        ObjectReflector::setProperty(
            $this->fileSourcePreparer,
            FileSourcePreparer::class,
            'fileStoreManager',
            $fileStoreManager
        );

        try {
            $this->fileSourcePreparer->prepare($source);
            self::fail(FileSourcePreparationException::class . ' not thrown');
        } catch (FileSourcePreparationException $fileSourcePreparationException) {
            self::assertEquals(new FileSourcePreparationException($exception), $fileSourcePreparationException);
        }

        self::assertCount(0, $this->sourceRepository->findAll());
    }

    public function testPrepareSuccess(): void
    {
        $fileSource = new FileSource(UserId::create(), 'file source label');
        $this->fixtureCreator->copyFixturesTo($fileSource->getPath());

        $runSource = $this->fileSourcePreparer->prepare($fileSource);

        self::assertCount(2, $this->sourceRepository->findAll());
        self::assertSame(
            scandir($this->fileStoreManager->createPath($fileSource)),
            scandir($this->fileStoreManager->createPath($runSource))
        );
    }
}
