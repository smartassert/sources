<?php

declare(strict_types=1);

namespace App\Tests\Unit\Model;

use App\Exception\NonAbsolutePathException;
use App\Model\AbsoluteFileLocator;
use PHPUnit\Framework\TestCase;

class AbsoluteFileLocatorTest extends TestCase
{
    public function testNonAbsolutePathThrowsException(): void
    {
        $path = 'relative';

        self::expectExceptionObject(new NonAbsolutePathException($path));

        new AbsoluteFileLocator($path);
    }

    public function testCreateSuccess(): void
    {
        $path = '/absolute';

        $absoluteFileLocator = new AbsoluteFileLocator($path);

        self::assertSame($path, $absoluteFileLocator->getPath());
        self::assertSame($path, (string) $absoluteFileLocator);
    }
}
