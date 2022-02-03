<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\FileSource;
use App\Tests\Model\UserId;
use PHPUnit\Framework\TestCase;

class FileSourceTest extends TestCase
{
    public function testGetPathEqualsToString(): void
    {
        $userId = UserId::create();
        $source = new FileSource($userId, 'file source label');
        $expectedPath = sprintf('%s/%s', $userId, $source->getId());

        self::assertSame($expectedPath, $source->getPath());
        self::assertSame($expectedPath, (string) $source);
        self::assertSame($source->getPath(), (string) $source);
    }
}
