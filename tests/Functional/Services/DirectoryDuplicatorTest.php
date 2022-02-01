<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\FileSource;
use App\Entity\RunSource;
use App\Exception\DirectoryDuplicationException;
use App\Exception\File\MirrorException;
use App\Exception\File\NotExistsException;
use App\Services\DirectoryDuplicator;
use App\Services\FileStoreManager;
use App\Tests\Model\UserId;
use App\Tests\Services\FileStoreFixtureCreator;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use webignition\ObjectReflector\ObjectReflector;

class DirectoryDuplicatorTest extends WebTestCase
{
    private DirectoryDuplicator $directoryDuplicator;
    private FileStoreFixtureCreator $fixtureCreator;
    private string $fileStoreBasePath;

    protected function setUp(): void
    {
        parent::setUp();

        $directoryDuplicator = self::getContainer()->get(DirectoryDuplicator::class);
        \assert($directoryDuplicator instanceof DirectoryDuplicator);
        $this->directoryDuplicator = $directoryDuplicator;

        $fixtureCreator = self::getContainer()->get(FileStoreFixtureCreator::class);
        \assert($fixtureCreator instanceof FileStoreFixtureCreator);
        $this->fixtureCreator = $fixtureCreator;

        $fileStoreBasePath = self::getContainer()->getParameter('file_store_base_path');
        \assert(is_string($fileStoreBasePath));
        $this->fileStoreBasePath = $fileStoreBasePath;
    }

    public function testDuplicateFileStoreManagerFileException(): void
    {
        $nonExistentSourcePath = '/path/to/source/does/not/exist';

        $source = new FileSource(UserId::create(), 'file source label');
        $fileException = (new NotExistsException($this->fileStoreBasePath . $nonExistentSourcePath))
            ->withContext('source')
        ;

        $target = new RunSource($source);

        try {
            $this->directoryDuplicator->duplicate('/path/to/source/does/not/exist', (string) $target);
            self::fail(DirectoryDuplicationException::class . ' not thrown');
        } catch (DirectoryDuplicationException $directoryDuplicatorException) {
            self::assertEquals(new DirectoryDuplicationException($fileException), $directoryDuplicatorException);
        }
    }

    public function testDuplicateFileSourceMirrorCallMirrorException(): void
    {
        $source = new FileSource(UserId::create(), 'file source label');
        $sourceAbsolutePath = $this->fileStoreBasePath . '/' . $source;
        $this->fixtureCreator->copyFixturesTo($source->getPath());

        $target = new RunSource($source);
        $targetAbsolutePath = $this->fileStoreBasePath . '/' . $target;

        $fileStoreManager = ObjectReflector::getProperty($this->directoryDuplicator, 'fileStoreManager');
        \assert($fileStoreManager instanceof FileStoreManager);

        $filesystem = ObjectReflector::getProperty($fileStoreManager, 'filesystem');
        \assert($filesystem instanceof Filesystem);

        $mirrorException = new MirrorException(
            $sourceAbsolutePath,
            $targetAbsolutePath,
            \Mockery::mock(IOException::class)
        );

        $mockFilesystem = \Mockery::mock($filesystem);
        $mockFilesystem
            ->shouldReceive('mirror')
            ->with($sourceAbsolutePath, $targetAbsolutePath)
            ->andThrow($mirrorException)
        ;

        ObjectReflector::setProperty($fileStoreManager, FileStoreManager::class, 'filesystem', $mockFilesystem);
        ObjectReflector::setProperty(
            $this->directoryDuplicator,
            DirectoryDuplicator::class,
            'fileStoreManager',
            $fileStoreManager
        );

        $directoryDuplicatorException = null;

        try {
            $this->directoryDuplicator->duplicate((string) $source, (string) $target);
            self::fail(DirectoryDuplicationException::class . ' not thrown');
        } catch (DirectoryDuplicationException $directoryDuplicatorException) {
        }

        self::assertInstanceOf(DirectoryDuplicationException::class, $directoryDuplicatorException);
        self::assertEquals(new DirectoryDuplicationException($mirrorException), $directoryDuplicatorException);
        self::assertDirectoryDoesNotExist($targetAbsolutePath);
    }

    public function testDuplicateSuccess(): void
    {
        $source = new FileSource(UserId::create(), 'file source label');
        $this->fixtureCreator->copyFixturesTo($source->getPath());

        $target = new RunSource($source);
        $this->directoryDuplicator->duplicate((string) $source, (string) $target);

        $fileStoreBasePath = self::getContainer()->getParameter('file_store_base_path');
        \assert(is_string($fileStoreBasePath));

        $sourceAbsolutePath = $fileStoreBasePath . '/' . $source;
        $targetAbsolutePath = $fileStoreBasePath . '/' . $target;

        self::assertSame(scandir($sourceAbsolutePath), scandir($targetAbsolutePath));
    }
}
