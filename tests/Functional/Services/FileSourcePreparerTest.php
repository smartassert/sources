<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\FileSource;
use App\Entity\RunSource;
use App\Exception\File\MirrorException;
use App\Exception\File\NotExistsException;
use App\Exception\SourceMirrorException;
use App\Services\FileSourcePreparer;
use App\Tests\Mock\Services\MockFileStoreManager;
use App\Tests\Model\UserId;
use App\Tests\Services\FileStoreFixtureCreator;
use App\Tests\Services\Source\SourceRemover;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Path;
use webignition\ObjectReflector\ObjectReflector;

class FileSourcePreparerTest extends WebTestCase
{
    private FileSourcePreparer $fileSourcePreparer;
    private FileStoreFixtureCreator $fixtureCreator;

    protected function setUp(): void
    {
        parent::setUp();

        $fileSourcePreparer = self::getContainer()->get(FileSourcePreparer::class);
        \assert($fileSourcePreparer instanceof FileSourcePreparer);
        $this->fileSourcePreparer = $fileSourcePreparer;

        $fixtureCreator = self::getContainer()->get(FileStoreFixtureCreator::class);
        \assert($fixtureCreator instanceof FileStoreFixtureCreator);
        $this->fixtureCreator = $fixtureCreator;

        $sourceRemover = self::getContainer()->get(SourceRemover::class);
        if ($sourceRemover instanceof SourceRemover) {
            $sourceRemover->removeAll();
        }
    }

    public function testPrepareFileSourceMirrorCallFileException(): void
    {
        $source = new FileSource(UserId::create(), 'file source label');
        $fileException = new NotExistsException('path-does-not-exist');

        $runSource = new RunSource($source);

        $fileStoreManager = (new MockFileStoreManager())
            ->withMirrorCallThrowingException($fileException)
            ->getMock()
        ;

        ObjectReflector::setProperty(
            $this->fileSourcePreparer,
            FileSourcePreparer::class,
            'fileStoreManager',
            $fileStoreManager
        );

        try {
            $this->fileSourcePreparer->prepare($runSource);
            self::fail(SourceMirrorException::class . ' not thrown');
        } catch (SourceMirrorException $fileSourcePreparationException) {
            self::assertEquals(new SourceMirrorException($fileException), $fileSourcePreparationException);
        }
    }

    public function testPrepareFileSourceMirrorCallMirrorException(): void
    {
        $source = new FileSource(UserId::create(), 'file source label');
        $fileException = new MirrorException(
            '/path/to/source',
            '/path/to/target',
            \Mockery::mock(IOException::class)
        );

        $runSource = new RunSource($source);

        $fileStoreManager = (new MockFileStoreManager())
            ->withMirrorCallThrowingException($fileException)
            ->withRemoveCall((string) $runSource)
            ->getMock()
        ;

        ObjectReflector::setProperty(
            $this->fileSourcePreparer,
            FileSourcePreparer::class,
            'fileStoreManager',
            $fileStoreManager
        );

        try {
            $this->fileSourcePreparer->prepare($runSource);
            self::fail(SourceMirrorException::class . ' not thrown');
        } catch (SourceMirrorException $fileSourcePreparationException) {
            self::assertEquals(new SourceMirrorException($fileException), $fileSourcePreparationException);
        }
    }

    public function testPrepareSuccess(): void
    {
        $source = new FileSource(UserId::create(), 'file source label');
        $this->fixtureCreator->copyFixturesTo($source->getPath());

        $target = new RunSource($source);
        $this->fileSourcePreparer->prepare($target);

        $fileStoreBasePath = self::getContainer()->getParameter('file_store_base_path');
        \assert(is_string($fileStoreBasePath));

        $sourceAbsolutePath = Path::canonicalize($fileStoreBasePath . '/' . $source);
        $targetAbsolutePath = Path::canonicalize($fileStoreBasePath . '/' . $target);

        self::assertSame(scandir($sourceAbsolutePath), scandir($targetAbsolutePath));
    }
}
