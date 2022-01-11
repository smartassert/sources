<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services;

use App\Exception\File\CreateException;
use App\Exception\File\MirrorException;
use App\Exception\File\NotExistsException;
use App\Exception\File\OutOfScopeException;
use App\Exception\File\RemoveException;
use App\Model\AbsoluteFileLocator;
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
     * @dataProvider throwsOutOfScopeExceptionDataProvider
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
     * @dataProvider throwsOutOfScopeExceptionDataProvider
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
    public function throwsOutOfScopeExceptionDataProvider(): array
    {
        return [
            OutOfScopeException::class => [
                'fileStoreManager' => new FileStoreManager(
                    new AbsoluteFileLocator(self::BASE_PATH),
                    (new MockFileSystem())
                        ->getMock()
                ),
                'fileLocator' => (new MockFileLocator())
                    ->withToStringCall('..')
                    ->getMock(),
                'expected' => new OutOfScopeException('/absolute/base', self::BASE_PATH),
            ],
        ];
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
                    new AbsoluteFileLocator(self::BASE_PATH),
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
                    new AbsoluteFileLocator(self::BASE_PATH),
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
            'source ' . OutOfScopeException::class => [
                'fileStoreManager' => new FileStoreManager(
                    new AbsoluteFileLocator(self::BASE_PATH),
                    (new MockFileSystem())->getMock()
                ),
                'source' => (new MockFileLocator())
                    ->withToStringCall('../source-out-of-scope')
                    ->getMock(),
                'target' => $unusedLocator,
                'expected' => new OutOfScopeException('/absolute/base/source-out-of-scope', self::BASE_PATH),
            ],
            'target ' . OutOfScopeException::class => [
                'fileStoreManager' => new FileStoreManager(
                    new AbsoluteFileLocator(self::BASE_PATH),
                    (new MockFileSystem())->getMock()
                ),
                'source' => $sourceLocator,
                'target' => (new MockFileLocator())
                    ->withToStringCall('../target-out-of-scope')
                    ->getMock(),
                'expected' => new OutOfScopeException('/absolute/base/target-out-of-scope', self::BASE_PATH),
            ],
            'source ' . NotExistsException::class => [
                'fileStoreManager' => new FileStoreManager(
                    new AbsoluteFileLocator(self::BASE_PATH),
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
                    new AbsoluteFileLocator(self::BASE_PATH),
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
                    new AbsoluteFileLocator(self::BASE_PATH),
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
                    new AbsoluteFileLocator(self::BASE_PATH),
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
        $locator = (new MockFileLocator())->withToStringCall(self::FILE_LOCATOR_PATH)->getMock();

        $fileStoreManager = new FileStoreManager(
            new AbsoluteFileLocator(self::BASE_PATH),
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
