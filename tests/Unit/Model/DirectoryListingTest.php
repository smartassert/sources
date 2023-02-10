<?php

declare(strict_types=1);

namespace App\Tests\Unit\Model;

use App\Model\DirectoryListing;
use PHPUnit\Framework\TestCase;

class DirectoryListingTest extends TestCase
{
    /**
     * @dataProvider getPathsDataProvider
     *
     * @param non-empty-string[] $expected
     */
    public function testGetPaths(DirectoryListing $listing, array $expected): void
    {
        self::assertSame($expected, $listing->paths);
    }

    /**
     * @return array<mixed>
     */
    public function getPathsDataProvider(): array
    {
        return [
            'empty' => [
                'listing' => new DirectoryListing([]),
                'expected' => [],
            ],
            'root files only, sorted' => [
                'listing' => new DirectoryListing([
                    'apple',
                    'bat',
                    'zebra',
                ]),
                'expected' => [
                    'apple',
                    'bat',
                    'zebra',
                ],
            ],
            'root files only, unsorted' => [
                'listing' => new DirectoryListing([
                    'bat',
                    'apple',
                    'zebra',
                ]),
                'expected' => [
                    'apple',
                    'bat',
                    'zebra',
                ],
            ],
            'non-root files only, sorted' => [
                'listing' => new DirectoryListing([
                    'apple/apple',
                    'apple/bat',
                    'apple/zebra',
                    'bat/apple',
                    'zebra/bat',
                ]),
                'expected' => [
                    'apple/apple',
                    'apple/bat',
                    'apple/zebra',
                    'bat/apple',
                    'zebra/bat',
                ],
            ],
            'non-root files only, unsorted' => [
                'listing' => new DirectoryListing([
                    'apple/bat',
                    'bat/apple',
                    'zebra/bat',
                    'apple/apple',
                    'apple/zebra',
                ]),
                'expected' => [
                    'apple/apple',
                    'apple/bat',
                    'apple/zebra',
                    'bat/apple',
                    'zebra/bat',
                ],
            ],
            'root and non-root files, unsorted' => [
                'listing' => new DirectoryListing([
                    'apple/bat',
                    'bat/apple',
                    'file1',
                    'file3',
                    'apple/apricot/bat',
                    'zebra/bat',
                    'apple/apple',
                    'file2',
                    'apple/zebra/bat',
                    'apple/zebra',
                ]),
                'expected' => [
                    'apple/apple',
                    'apple/apricot/bat',
                    'apple/bat',
                    'apple/zebra',
                    'apple/zebra/bat',
                    'bat/apple',
                    'zebra/bat',
                    'file1',
                    'file2',
                    'file3',
                ],
            ],
        ];
    }
}
