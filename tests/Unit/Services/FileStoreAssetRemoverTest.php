<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services;

use App\Exception\FileStoreAssetRemoverException;
use App\Model\FileLocatorInterface;
use App\Model\FileStore;
use App\Services\FileStoreAssetRemover;
use App\Services\FileStoreFactory;
use App\Tests\Mock\Model\MockFileLocator;
use App\Tests\Mock\Symfony\Component\Filesystem\MockFileSystem;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class FileStoreAssetRemoverTest extends TestCase
{
    /**
     * @dataProvider removeThrowsExceptionDataProvider
     */
    public function testRemoveThrowsException(
        string $fileStoreFactoryBasePath,
        Filesystem $filesystem,
        FileLocatorInterface $fileLocator,
        \Exception $expected
    ): void {
        $remover = new FileStoreAssetRemover(new FileStoreFactory($fileStoreFactoryBasePath), $filesystem);

        $this->expectExceptionObject($expected);

        $remover->remove($fileLocator);
    }

    /**
     * @return array<mixed>
     */
    public function removeThrowsExceptionDataProvider(): array
    {
        $relativeBasePath = 'base/path';
        $absoluteBasePath = '/base/path';

        $validFileLocator = (new MockFileLocator())->withGetPathCall('file/path')->getMock();
        $sameAsBasePathFileLocator = (new MockFileLocator())->withGetPathCall('.')->getMock();
        $oneUpFromBasePathFileLocator = (new MockFileLocator())->withGetPathCall('..')->getMock();

        $validFileStore = new FileStore($absoluteBasePath, $validFileLocator);
        $validFileStorePath = (string) $validFileStore;

        $cannotRemoveIOException = new IOException('Failed to remove file "' . $validFileStorePath . '"');

        return [
            'not an absolute path' => [
                'fileStoreFactoryBasePath' => $relativeBasePath,
                'filesystem' => (new MockFileSystem())->getMock(),
                'fileLocator' => $validFileLocator,
                'expected' => FileStoreAssetRemoverException::createPathNotAbsoluteException(
                    new FileStore($relativeBasePath, $validFileLocator)
                ),
            ],
            'file location is file store base path' => [
                'fileStoreFactoryBasePath' => $absoluteBasePath,
                'filesystem' => (new MockFileSystem())
                    ->getMock(),
                'fileLocator' => $sameAsBasePathFileLocator,
                'expected' => FileStoreAssetRemoverException::createPathIsOutsideBasePathException(
                    new FileStore($absoluteBasePath, $sameAsBasePathFileLocator)
                ),
            ],
            'file location is one up from file store base path' => [
                'fileStoreFactoryBasePath' => $absoluteBasePath,
                'filesystem' => (new MockFileSystem())
                    ->getMock(),
                'fileLocator' => $oneUpFromBasePathFileLocator,
                'expected' => FileStoreAssetRemoverException::createPathIsOutsideBasePathException(
                    new FileStore($absoluteBasePath, $oneUpFromBasePathFileLocator)
                ),
            ],
            'filesystem IOException' => [
                'fileStoreFactoryBasePath' => $absoluteBasePath,
                'filesystem' => (new MockFileSystem())
                    ->withExistsCall($validFileStorePath, true)
                    ->withRemoveCallThrowingException($validFileStorePath, $cannotRemoveIOException)
                    ->getMock(),
                'fileLocator' => $validFileLocator,
                'expected' => FileStoreAssetRemoverException::createFilesystemErrorException(
                    $validFileStore,
                    $cannotRemoveIOException
                ),
            ],
        ];
    }

    public function testRemoveDoesNotExist(): void
    {
        $basePath = '/base/path';
        $fileLocator = (new MockFileLocator())->withGetPathCall('file/path')->getMock();
        $fileStorePath = (string) new FileStore($basePath, $fileLocator);

        $filesystem = (new MockFileSystem())
            ->withExistsCall($fileStorePath, false)
            ->getMock()
        ;

        $remover = new FileStoreAssetRemover(new FileStoreFactory($basePath), $filesystem);

        self::assertTrue($remover->remove($fileLocator));
    }
}
