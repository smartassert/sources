<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\FileSource;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Ulid;

class FileSourceTest extends TestCase
{
    public function testGetPathEqualsToString(): void
    {
        $id = (string) new Ulid();
        $userId = (string) new Ulid();

        $source = new FileSource($id, $userId, 'file source label');

        $expectedPath = sprintf('%s/%s', $userId, $id);

        self::assertSame($expectedPath, $source->getPath());
        self::assertSame($expectedPath, (string) $source);
        self::assertSame($source->getPath(), (string) $source);
    }
}
