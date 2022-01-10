<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services;

use App\Exception\File\OutOfScopeException;
use App\Model\AbsoluteFileLocator;
use App\Services\FileStorePathFactory;
use App\Tests\Mock\Model\MockFileLocator;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class FileStorePathFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const BASE_PATH = '/absolute/base/path';
    private const FILE_LOCATOR_PATH = 'file/locator/path';
    private const PATH = self::BASE_PATH . '/' . self::FILE_LOCATOR_PATH;

    private FileStorePathFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new FileStorePathFactory(new AbsoluteFileLocator(self::BASE_PATH));
    }

    public function testCreateThrowsException(): void
    {
        $fileLocator = (new MockFileLocator())->withToStringCall('..')->getMock();

        $this->expectException(OutOfScopeException::class);

        $this->factory->create($fileLocator);
    }

    public function testCreateSuccess(): void
    {
        $fileLocator = (new MockFileLocator())->withToStringCall(self::FILE_LOCATOR_PATH)->getMock();

        self::assertEquals(
            self::PATH,
            $this->factory->create($fileLocator)
        );
    }
}
