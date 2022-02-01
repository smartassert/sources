<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services;

use App\Entity\FileSource;
use App\Entity\RunSource;
use App\Exception\DirectoryDuplicationException;
use App\Services\DirectoryDuplicator;
use App\Services\FileSourcePreparer;
use App\Services\Source\Factory;
use App\Tests\Model\UserId;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FileSourcePreparerTest extends WebTestCase
{
    public function testPrepareDirectoryDuplicatorThrowsException(): void
    {
        $source = new FileSource(UserId::create(), 'file source label');
        $target = new RunSource($source);

        $sourceFactory = \Mockery::mock(Factory::class);
        $sourceFactory
            ->shouldReceive('createRunSource')
            ->with($source)
            ->andReturn($target)
        ;

        $directoryDuplicatorException = \Mockery::mock(DirectoryDuplicationException::class);

        $directoryDuplicator = \Mockery::mock(DirectoryDuplicator::class);
        $directoryDuplicator
            ->shouldReceive('duplicate')
            ->with((string) $source, (string) $target)
            ->andThrow($directoryDuplicatorException)
        ;

        self::expectExceptionObject($directoryDuplicatorException);
        (new FileSourcePreparer($sourceFactory, $directoryDuplicator))->prepare($source);
    }
}
