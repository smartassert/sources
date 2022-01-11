<?php

declare(strict_types=1);

namespace App\Tests\Unit\Model;

use App\Exception\File\NonAbsolutePathException;
use App\Exception\File\OutOfScopeException;
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

    /**
     * @dataProvider appendDataProvider
     */
    public function testAppend(AbsoluteFileLocator $locator, string $path, string $expected): void
    {
        self::assertSame($expected, (string) $locator->append($path));
    }

    /**
     * @return array<mixed>
     */
    public function appendDataProvider(): array
    {
        return [
            'locator is root, path is empty' => [
                'locator' => new AbsoluteFileLocator('/'),
                'path' => '',
                'expected' => '/'
            ],
            'locator is root, path is root' => [
                'locator' => new AbsoluteFileLocator('/'),
                'path' => '/',
                'expected' => '/'
            ],
            'locator is root, path is relative' => [
                'locator' => new AbsoluteFileLocator('/'),
                'path' => 'relative',
                'expected' => '/relative'
            ],
            'locator is root, path is absolute' => [
                'locator' => new AbsoluteFileLocator('/'),
                'path' => '/absolute',
                'expected' => '/absolute'
            ],
            'locator is non-root, path is empty' => [
                'locator' => new AbsoluteFileLocator('/path'),
                'path' => '',
                'expected' => '/path'
            ],
            'locator is non-root, path is root' => [
                'locator' => new AbsoluteFileLocator('/path'),
                'path' => '/',
                'expected' => '/path'
            ],
            'locator is non-root, path is relative' => [
                'locator' => new AbsoluteFileLocator('/path'),
                'path' => 'relative',
                'expected' => '/path/relative'
            ],
            'locator is non-root, path is absolute' => [
                'locator' => new AbsoluteFileLocator('/path'),
                'path' => '/absolute',
                'expected' => '/path/absolute'
            ],
            'path is canonicalized' => [
                'locator' => new AbsoluteFileLocator('/path/to/locator'),
                'path' => '/./has/been/updated',
                'expected' => '/path/to/locator/has/been/updated'
            ],
        ];
    }

    public function testAppendThrowsOutOfScopeException(): void
    {
        $locator = new AbsoluteFileLocator('/path/to/locator');
        $path = '..';

        $this->expectException(OutOfScopeException::class);
        $locator->append($path);
    }
}
