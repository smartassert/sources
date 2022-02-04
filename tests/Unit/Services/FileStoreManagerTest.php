<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services;

use App\Exception\File\CreateException;
use App\Exception\File\OutOfScopeException;
use App\Exception\File\RemoveException;
use App\Exception\File\WriteException;
use App\Model\AbsoluteFileLocator;
use App\Services\FileStoreManager;
use App\Tests\Mock\Symfony\Component\Filesystem\MockFileSystem;
use League\Flysystem\Filesystem;
use League\Flysystem\UnableToWriteFile;
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
                    (new MockFileSystem())->getMock(),
                    \Mockery::mock(Filesystem::class),
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
                        ->getMock(),
                    \Mockery::mock(Filesystem::class),
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
                        ->getMock(),
                    \Mockery::mock(Filesystem::class),
                ),
                'relativePath' => self::FILE_LOCATOR_PATH,
                'expected' => new RemoveException(self::PATH, $cannotRemoveIOException),
            ],
        ];
    }

    public function testAddThrowsException(): void
    {
        $fileRelativePath = 'path/to/file.txt';
        $content = 'file content';

        $flysystemException = new UnableToWriteFile();

        $flyFilesystem = \Mockery::mock(Filesystem::class);
        $flyFilesystem
            ->shouldReceive('write')
            ->with($fileRelativePath, $content)
            ->andThrow($flysystemException)
        ;

        $fileStoreManager = new FileStoreManager(
            new AbsoluteFileLocator(self::BASE_PATH),
            (new MockFileSystem())->getMock(),
            $flyFilesystem,
        );

        $this->expectExceptionObject(new WriteException($fileRelativePath, $flysystemException));

        $fileStoreManager->add($fileRelativePath, $content);
    }
}
