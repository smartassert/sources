<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services;

use App\Exception\Storage\RemoveException;
use App\Exception\Storage\WriteException;
use App\Services\FileStoreInterface;
use App\Services\FileStoreManager;
use League\Flysystem\Filesystem;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToWriteFile;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class FileStoreManagerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const BASE_PATH = '/absolute/base/path';
    private const FILE_LOCATOR_PATH = 'file/locator/path';
    private const PATH = self::BASE_PATH . '/' . self::FILE_LOCATOR_PATH;

    /**
     * @dataProvider throwsRemoveExceptionDataProvider
     */
    public function testRemoveThrowsException(
        FileStoreInterface $fileStoreManager,
        string $relativePath,
        \Exception $expected
    ): void {
        $this->expectExceptionObject($expected);

        $fileStoreManager->remove($relativePath);
    }

    /**
     * @return array<mixed>
     */
    public function throwsRemoveExceptionDataProvider(): array
    {
        $cannotRemoveException = UnableToDeleteDirectory::atLocation(self::PATH);

        $mockFilesystem = (\Mockery::mock(Filesystem::class));
        $mockFilesystem
            ->shouldReceive('deleteDirectory')
            ->with(self::FILE_LOCATOR_PATH)
            ->andThrow($cannotRemoveException)
        ;

        return [
            RemoveException::class => [
                'fileStoreManager' => new FileStoreManager($mockFilesystem),
                'relativePath' => self::FILE_LOCATOR_PATH,
                'expected' => new RemoveException(self::FILE_LOCATOR_PATH, $cannotRemoveException),
            ],
        ];
    }

    public function testWriteThrowsException(): void
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

        $fileStoreManager = new FileStoreManager($flyFilesystem);

        $this->expectExceptionObject(new WriteException($fileRelativePath, $flysystemException));

        $fileStoreManager->write($fileRelativePath, $content);
    }
}
