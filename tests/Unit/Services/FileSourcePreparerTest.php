<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services;

use App\Entity\FileSource;
use App\Entity\RunSource;
use App\Exception\DirectoryDuplicationException;
use App\Services\DirectoryDuplicator;
use App\Services\FileSourcePreparer;
use App\Tests\Model\UserId;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Uid\Ulid;

class FileSourcePreparerTest extends WebTestCase
{
    public function testPrepareDirectoryDuplicatorThrowsException(): void
    {
        $source = new FileSource(UserId::create(), 'file source label');
        $target = new RunSource($source);

        $directoryDuplicatorException = \Mockery::mock(DirectoryDuplicationException::class);

        $directoryDuplicator = \Mockery::mock(DirectoryDuplicator::class);
        $directoryDuplicator
            ->shouldReceive('duplicate')
            ->withArgs(function (string $sourcePath, string $targetPath) use ($source) {
                self::assertSame((string) $source, $sourcePath);

                $sourcePathParts = explode('/', $sourcePath);
                $targetPathParts = explode('/', $targetPath);

                self::assertSame($sourcePathParts[0], $targetPathParts[0]);
                self::assertTrue(Ulid::isValid($targetPathParts[1]));

                return true;
            })
            ->andThrow($directoryDuplicatorException)
        ;

        self::expectExceptionObject($directoryDuplicatorException);
        (new FileSourcePreparer($directoryDuplicator))->prepare($source);
    }
}
