<?php

declare(strict_types=1);

namespace App\Tests\Unit\Model;

use App\Model\FileLocatorInterface;
use App\Model\FileStore;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class FileStoreTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testGetPathEqualsToString(): void
    {
        $fileLocatorPath = 'file-locator-path';

        $fileLocator = \Mockery::mock(FileLocatorInterface::class);
        $fileLocator
            ->shouldReceive('getPath')
            ->andReturn($fileLocatorPath)
        ;

        $fileStoreBasePath = '/file-store-base-path';
        $fileStore = new FileStore($fileStoreBasePath, $fileLocator);

        $expectedPath = sprintf('%s/%s', $fileStoreBasePath, $fileLocatorPath);

        self::assertSame($expectedPath, $fileStore->getPath());
        self::assertSame($expectedPath, (string) $fileStore);
        self::assertSame($fileStore->getPath(), (string) $fileStore);
    }
}
