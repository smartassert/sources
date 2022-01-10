<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services;

use App\Exception\FilePath\CreateException;
use App\Exception\FilePath\NotExistsException;
use App\Exception\FilePath\RemoveException;
use App\Exception\FileStore\MirrorException;
use App\Exception\FileStore\NonAbsolutePathException;
use App\Exception\FileStore\OutOfScopeException;
use App\Model\FileLocatorInterface;
use App\Services\FileStoreManager;
use App\Tests\Mock\Model\MockFileLocator;
use App\Tests\Mock\Symfony\Component\Filesystem\MockFileSystem;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Exception\IOException;

class FileStoreManagerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const BASE_PATH = '/absolute/base/path';
    private const FILE_LOCATOR_PATH = 'file/locator/path';
    private const PATH = self::BASE_PATH . '/' . self::FILE_LOCATOR_PATH;

    /**
     * @dataProvider singleLocatorMethodThrowsExceptionDataProvider
     *
     * @param callable(FileStoreManager, FileLocatorInterface): void $action
     */
    public function testSingleLocatorMethodThrowsException(
        FileStoreManager $fileStoreManager,
        FileLocatorInterface $fileLocator,
        callable $action,
        \Exception $expected
    ): void {
        $this->expectExceptionObject($expected);

        $action($fileStoreManager, $fileLocator);
    }

    /**
     * @return array<mixed>
     */
    public function singleLocatorMethodThrowsExceptionDataProvider(): array
    {
        $relativeBasePath = 'relative-base-path';
        $relativeBasePathFileStoreManager = new FileStoreManager($relativeBasePath, (new MockFileSystem())->getMock());
        $validFileStoreManager = new FileStoreManager(self::BASE_PATH, (new MockFileSystem())->getMock());

        $outOfScopeFileLocator = (new MockFileLocator())->withToStringCall('..')->getMock();
        $validFileLocator = (new MockFileLocator())->withToStringCall(self::FILE_LOCATOR_PATH)->getMock();

        $createPathAction = function (FileStoreManager $fileStoreManager, FileLocatorInterface $fileLocator) {
            $fileStoreManager->createPath($fileLocator);
        };

        $initializeAction = function (FileStoreManager $fileStoreManager, FileLocatorInterface $fileLocator) {
            $fileStoreManager->initialize($fileLocator);
        };

        $removeAction = function (FileStoreManager $fileStoreManager, FileLocatorInterface $fileLocator) {
            $fileStoreManager->remove($fileLocator);
        };

        $existsAction = function (FileStoreManager $fileStoreManager, FileLocatorInterface $fileLocator) {
            $fileStoreManager->exists($fileLocator);
        };

        $expectedNonAbsolutePathException = new NonAbsolutePathException($relativeBasePath);
        $expectedOutOfScopeException = new OutOfScopeException('/absolute/base', self::BASE_PATH);

        return [
            'createPath ' . NonAbsolutePathException::class => [
                'fileStoreManager' => $relativeBasePathFileStoreManager,
                'fileLocator' => $validFileLocator,
                'action' => $createPathAction,
                'expected' => $expectedNonAbsolutePathException,
            ],
            'createPath ' . OutOfScopeException::class => [
                'fileStoreManager' => $validFileStoreManager,
                'fileLocator' => $outOfScopeFileLocator,
                'action' => $createPathAction,
                'expected' => $expectedOutOfScopeException,
            ],
            'initialize ' . NonAbsolutePathException::class => [
                'fileStoreManager' => $relativeBasePathFileStoreManager,
                'fileLocator' => $validFileLocator,
                'action' => $initializeAction,
                'expected' => $expectedNonAbsolutePathException,
            ],
            'initialize ' . OutOfScopeException::class => [
                'fileStoreManager' => $validFileStoreManager,
                'fileLocator' => $outOfScopeFileLocator,
                'action' => $initializeAction,
                'expected' => $expectedOutOfScopeException,
            ],
            'remove ' . NonAbsolutePathException::class => [
                'fileStoreManager' => $relativeBasePathFileStoreManager,
                'fileLocator' => $validFileLocator,
                'action' => $removeAction,
                'expected' => $expectedNonAbsolutePathException,
            ],
            'remove ' . OutOfScopeException::class => [
                'fileStoreManager' => $validFileStoreManager,
                'fileLocator' => $outOfScopeFileLocator,
                'action' => $removeAction,
                'expected' => $expectedOutOfScopeException,
            ],
            'exists ' . NonAbsolutePathException::class => [
                'fileStoreManager' => $relativeBasePathFileStoreManager,
                'fileLocator' => $validFileLocator,
                'action' => $existsAction,
                'expected' => $expectedNonAbsolutePathException,
            ],
            'exists ' . OutOfScopeException::class => [
                'fileStoreManager' => $validFileStoreManager,
                'fileLocator' => $outOfScopeFileLocator,
                'action' => $existsAction,
                'expected' => $expectedOutOfScopeException,
            ],
        ];
    }

    public function testCreatePathSuccess(): void
    {
        $fileStoreManager = new FileStoreManager(self::BASE_PATH, (new MockFileSystem())->getMock());
        $fileLocator = (new MockFileLocator())->withToStringCall(self::FILE_LOCATOR_PATH)->getMock();

        self::assertEquals(
            self::PATH,
            $fileStoreManager->createPath($fileLocator)
        );
    }

    /**
     * @dataProvider throwsRemoveExceptionDataProvider
     * @dataProvider throwsCreateExceptionDataProvider
     */
    public function testInitializeThrowsException(
        FileStoreManager $fileStoreManager,
        FileLocatorInterface $fileLocator,
        \Exception $expected
    ): void {
        $this->expectExceptionObject($expected);

        $fileStoreManager->initialize($fileLocator);
    }

    /**
     * @dataProvider throwsRemoveExceptionDataProvider
     */
    public function testRemoveThrowsException(
        FileStoreManager $fileStoreManager,
        FileLocatorInterface $fileLocator,
        \Exception $expected
    ): void {
        $this->expectExceptionObject($expected);

        $fileStoreManager->remove($fileLocator);
    }

    /**
     * @return array<mixed>
     */
    public function throwsCreateExceptionDataProvider(): array
    {
        $cannotCreateIOException = new IOException('Failed to create "' . self::PATH . '"');

        return [
            CreateException::class => [
                'fileStoreManager' => new FileStoreManager(
                    self::BASE_PATH,
                    (new MockFileSystem())
                        ->withExistsCall(self::PATH, true)
                        ->withRemoveCall(self::PATH)
                        ->withMkdirCallThrowingException(self::PATH, $cannotCreateIOException)
                        ->getMock()
                ),
                'fileLocator' => (new MockFileLocator())
                    ->withToStringCall(self::FILE_LOCATOR_PATH)
                    ->getMock(),
                'expected' => new CreateException(self::PATH, $cannotCreateIOException),
            ],
        ];
    }

    /**
     * @return array<mixed>
     */
    public function throwsRemoveExceptionDataProvider(): array
    {
        $cannotRemoveIOException = new IOException('Failed to remove file "' . self::PATH . '"');

        return [
            RemoveException::class => [
                'fileStoreManager' => new FileStoreManager(
                    self::BASE_PATH,
                    (new MockFileSystem())
                        ->withExistsCall(self::PATH, true)
                        ->withRemoveCallThrowingException(self::PATH, $cannotRemoveIOException)
                        ->getMock()
                ),
                'fileLocator' => (new MockFileLocator())
                    ->withToStringCall(self::FILE_LOCATOR_PATH)
                    ->getMock(),
                'expected' => new RemoveException(self::PATH, $cannotRemoveIOException),
            ],
        ];
    }

    /**
     * @dataProvider mirrorThrowsExceptionDataProvider
     */
    public function testMirrorThrowsExceptionDataProvider(
        FileStoreManager $fileStoreManager,
        FileLocatorInterface $source,
        FileLocatorInterface $target,
        \Exception $expected
    ): void {
        $this->expectExceptionObject($expected);

        $fileStoreManager->mirror($source, $target);
    }

    /**
     * @return array<mixed>
     */
    public function mirrorThrowsExceptionDataProvider(): array
    {
        $relativeBasePath = 'relative-base-path';
        $sourceLocatorPath = 'source-path';
        $targetLocatorPath = 'target-path';
        $sourcePath = self::BASE_PATH . '/' . $sourceLocatorPath;
        $targetPath = self::BASE_PATH . '/' . $targetLocatorPath;

        $sourceLocator = (new MockFileLocator())->withToStringCall($sourceLocatorPath)->getMock();
        $targetLocator = (new MockFileLocator())->withToStringCall($targetLocatorPath)->getMock();
        $unusedLocator = (new MockFileLocator())->getMock();

        $cannotCreateIOException = new IOException('Failed to create "' . $targetPath . '"');
        $cannotRemoveIOException = new IOException('Failed to remove file "' . $targetPath . '"');
        $cannotMirrorIOException = new IOException('Unable to guess "/var/foo" file type.');

        return [
            'source ' . NonAbsolutePathException::class => [
                'fileStoreManager' => new FileStoreManager($relativeBasePath, (new MockFileSystem())->getMock()),
                'source' => $sourceLocator,
                'target' => $unusedLocator,
                'expected' => new NonAbsolutePathException($relativeBasePath),
            ],
            'source ' . OutOfScopeException::class => [
                'fileStoreManager' => new FileStoreManager(self::BASE_PATH, (new MockFileSystem())->getMock()),
                'source' => (new MockFileLocator())
                    ->withToStringCall('..')
                    ->getMock(),
                'target' => $unusedLocator,
                'expected' => new OutOfScopeException('/absolute/base', self::BASE_PATH),
            ],
            'target ' . OutOfScopeException::class => [
                'fileStoreManager' => new FileStoreManager(self::BASE_PATH, (new MockFileSystem())->getMock()),
                'source' => (new MockFileLocator())
                    ->withToStringCall('../..')
                    ->getMock(),
                'target' => $unusedLocator,
                'expected' => new OutOfScopeException('/absolute', self::BASE_PATH),
            ],
            'source ' . NotExistsException::class => [
                'fileStoreManager' => new FileStoreManager(
                    self::BASE_PATH,
                    (new MockFileSystem())
                        ->withExistsCall($sourcePath, false)
                        ->getMock()
                ),
                'source' => $sourceLocator,
                'target' => $targetLocator,
                'expected' => new NotExistsException($sourcePath),
            ],
            'target ' . RemoveException::class => [
                'fileStoreManager' => new FileStoreManager(
                    self::BASE_PATH,
                    (new MockFileSystem())
                        ->withExistsCall($sourcePath, true)
                        ->withRemoveCallThrowingException($targetPath, $cannotRemoveIOException)
                        ->getMock()
                ),
                'source' => $sourceLocator,
                'target' => $targetLocator,
                'expected' => new RemoveException($targetPath, $cannotRemoveIOException),
            ],
            'target ' . CreateException::class => [
                'fileStoreManager' => new FileStoreManager(
                    self::BASE_PATH,
                    (new MockFileSystem())
                        ->withExistsCall($sourcePath, true)
                        ->withRemoveCall($targetPath)
                        ->withMkdirCallThrowingException($targetPath, $cannotCreateIOException)
                        ->getMock()
                ),
                'source' => $sourceLocator,
                'target' => $targetLocator,
                'expected' => new CreateException($targetPath, $cannotCreateIOException),
            ],
            MirrorException::class => [
                'fileStoreManager' => new FileStoreManager(
                    self::BASE_PATH,
                    (new MockFileSystem())
                        ->withExistsCall($sourcePath, true)
                        ->withRemoveCall($targetPath)
                        ->withMkdirCall($targetPath)
                        ->withMirrorCallThrowingException($sourcePath, $targetPath, $cannotMirrorIOException)
                        ->getMock()
                ),
                'source' => $sourceLocator,
                'target' => $targetLocator,
                'expected' => new MirrorException($sourcePath, $targetPath, $cannotMirrorIOException),
            ],
        ];
    }

    public function testMirrorSourcePathAndTargetPathAreEqual(): void
    {
        $locator = (new MockFileLocator())->withToStringCall(self::PATH)->getMock();

        $fileStoreManager = new FileStoreManager(
            self::BASE_PATH,
            (new MockFileSystem())
                ->withExistsCall(self::PATH, true)
                ->withoutRemoveCall()
                ->withoutMkdirCall()
                ->withoutMirrorCall()
                ->getMock()
        );

        $fileStoreManager->mirror($locator, $locator);
    }
}
