<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services;

use App\Exception\File\CreateException;
use App\Exception\File\MirrorException;
use App\Exception\File\NotExistsException;
use App\Exception\File\OutOfScopeException;
use App\Exception\File\RemoveException;
use App\Model\AbsoluteFileLocator;
use App\Services\FileStoreManager;
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
     * @dataProvider throwsCreateExceptionDataProvider
     */
    public function testCreateThrowsException(
        FileStoreManager $fileStoreManager,
        string $relativePath,
        \Exception $expected
    ): void {
        $this->expectExceptionObject($expected);

        $fileStoreManager->create($relativePath);
    }

    /**
     * @dataProvider throwsOutOfScopeExceptionDataProvider
     * @dataProvider throwsRemoveExceptionDataProvider
     */
    public function testRemoveThrowsException(
        FileStoreManager $fileStoreManager,
        string $relativePath,
        \Exception $expected
    ): void {
        $this->expectExceptionObject($expected);

        $fileStoreManager->remove($relativePath);
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
                'relativePath' => '..',
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
                'relativePath' => self::FILE_LOCATOR_PATH,
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
                'relativePath' => self::FILE_LOCATOR_PATH,
                'expected' => new RemoveException(self::PATH, $cannotRemoveIOException),
            ],
        ];
    }

    /**
     * @dataProvider mirrorThrowsExceptionDataProvider
     */
    public function testMirrorThrowsExceptionDataProvider(
        FileStoreManager $fileStoreManager,
        string $sourceRelativePath,
        string $targetRelativePath,
        \Exception $expected
    ): void {
        $this->expectExceptionObject($expected);

        $fileStoreManager->mirror($sourceRelativePath, $targetRelativePath);
    }

    /**
     * @return array<mixed>
     */
    public function mirrorThrowsExceptionDataProvider(): array
    {
        $sourceRelativePath = 'source-path';
        $targetRelativePath = 'target-path';

        $sourcePath = self::BASE_PATH . '/' . $sourceRelativePath;
        $targetPath = self::BASE_PATH . '/' . $targetRelativePath;

        $cannotCreateIOException = new IOException('Failed to create "' . $targetPath . '"');
        $cannotRemoveIOException = new IOException('Failed to remove file "' . $targetPath . '"');
        $cannotMirrorIOException = new IOException('Unable to guess "/var/foo" file type.');

        return [
            'source ' . OutOfScopeException::class => [
                'fileStoreManager' => new FileStoreManager(
                    new AbsoluteFileLocator(self::BASE_PATH),
                    (new MockFileSystem())->getMock()
                ),
                'sourceRelativePath' => '../source-out-of-scope',
                'targetRelativePath' => $targetRelativePath,
                'expected' => (new OutOfScopeException(
                    '/absolute/base/source-out-of-scope',
                    self::BASE_PATH
                ))->withContext('source'),
            ],
            'target ' . OutOfScopeException::class => [
                'fileStoreManager' => new FileStoreManager(
                    new AbsoluteFileLocator(self::BASE_PATH),
                    (new MockFileSystem())->getMock()
                ),
                'sourceRelativePath' => $sourceRelativePath,
                'targetRelativePath' => '../target-out-of-scope',
                'expected' => (new OutOfScopeException(
                    '/absolute/base/target-out-of-scope',
                    self::BASE_PATH
                ))->withContext('target'),
            ],
            'source ' . NotExistsException::class => [
                'fileStoreManager' => new FileStoreManager(
                    new AbsoluteFileLocator(self::BASE_PATH),
                    (new MockFileSystem())
                        ->withExistsCall($sourcePath, false)
                        ->getMock()
                ),
                'sourceRelativePath' => $sourceRelativePath,
                'targetRelativePath' => $targetRelativePath,
                'expected' => (new NotExistsException($sourcePath))->withContext('source'),
            ],
            'target ' . RemoveException::class => [
                'fileStoreManager' => new FileStoreManager(
                    new AbsoluteFileLocator(self::BASE_PATH),
                    (new MockFileSystem())
                        ->withExistsCall($sourcePath, true)
                        ->withRemoveCallThrowingException($targetPath, $cannotRemoveIOException)
                        ->getMock()
                ),
                'sourceRelativePath' => $sourceRelativePath,
                'targetRelativePath' => $targetRelativePath,
                'expected' => (new RemoveException($targetPath, $cannotRemoveIOException))->withContext('target'),
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
                'sourceRelativePath' => $sourceRelativePath,
                'targetRelativePath' => $targetRelativePath,
                'expected' => (new CreateException($targetPath, $cannotCreateIOException))->withContext('target'),
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
                'sourceRelativePath' => $sourceRelativePath,
                'targetRelativePath' => $targetRelativePath,
                'expected' => new MirrorException($sourcePath, $targetPath, $cannotMirrorIOException),
            ],
        ];
    }

    public function testMirrorSourcePathAndTargetPathAreEqual(): void
    {
        $fileStoreManager = new FileStoreManager(
            new AbsoluteFileLocator(self::BASE_PATH),
            (new MockFileSystem())
                ->withExistsCall(self::PATH, true)
                ->withoutRemoveCall()
                ->withoutMkdirCall()
                ->withoutMirrorCall()
                ->getMock()
        );

        $fileStoreManager->mirror(self::FILE_LOCATOR_PATH, self::FILE_LOCATOR_PATH);
    }
}
