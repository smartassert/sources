<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services;

use App\Exception\Storage\WriteException;
use App\Services\FileStoreManager;
use League\Flysystem\Filesystem;
use League\Flysystem\UnableToWriteFile;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class FileStoreManagerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

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
