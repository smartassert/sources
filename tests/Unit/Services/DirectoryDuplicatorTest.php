<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services;

use App\Exception\DirectoryDuplicator\DuplicationException;
use App\Exception\DirectoryDuplicator\MissingSourceException;
use App\Exception\DirectoryDuplicator\TargetCreationException;
use App\Exception\DirectoryDuplicator\TargetRemovalException;
use App\Model\FileLocatorInterface;
use App\Services\DirectoryDuplicator;
use App\Tests\Mock\Model\MockFileLocator;
use App\Tests\Mock\Symfony\Component\Filesystem\MockFileSystem;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class DirectoryDuplicatorTest extends TestCase
{
    /**
     * @dataProvider duplicateThrowsExceptionDataProvider
     */
    public function testDuplicateThrowsException(
        Filesystem $filesystem,
        FileLocatorInterface $source,
        FileLocatorInterface $target,
        \Exception $expected
    ): void {
        $directoryDuplicator = new DirectoryDuplicator($filesystem);

        $this->expectExceptionObject($expected);

        $directoryDuplicator->duplicate($source, $target);
    }

    /**
     * @return array<mixed>
     */
    public function duplicateThrowsExceptionDataProvider(): array
    {
        $source = (new MockFileLocator())
            ->withToStringCall('/path/to/source')
            ->getMock()
        ;

        $doesNotExistFilesystem = (new MockFileSystem())
            ->withExistsCall((string) $source, false)
            ->getMock()
        ;

        $target = (new MockFileLocator())
            ->withToStringCall('/path/to/target')
            ->getMock()
        ;

        $cannotRemoveIOException = new IOException('Failed to remove file "' . $target . '"');
        $cannotCreateIOException = new IOException('Failed to create "' . $target . '"');
        $cannotMirrorIOException = new IOException('Unable to guess "/var/foo" file type.');

        $targetCannotBeRemovedFilesystem = (new MockFileSystem())
            ->withExistsCall((string) $source, true)
            ->withRemoveCallThrowingException((string) $target, $cannotRemoveIOException)
            ->getMock()
        ;

        $targetCannotBeCreatedFilesystem = (new MockFileSystem())
            ->withExistsCall((string) $source, true)
            ->withRemoveCall((string) $target)
            ->withMkdirCallThrowingException((string) $target, $cannotCreateIOException)
            ->getMock()
        ;

        $sourceCannotBeMirrored = (new MockFileSystem())
            ->withExistsCall((string) $source, true)
            ->withRemoveCall((string) $target)
            ->withMkdirCall((string) $target)
            ->withMirrorCallThrowingException((string) $source, (string) $target, $cannotMirrorIOException)
            ->getMock()
        ;

        return [
            'source missing' => [
                'filesystem' => $doesNotExistFilesystem,
                'source' => $source,
                'target' => $target,
                'expected' => new MissingSourceException($source),
            ],
            'target cannot be removed' => [
                'filesystem' => $targetCannotBeRemovedFilesystem,
                'source' => $source,
                'target' => $target,
                'expected' => new TargetRemovalException($target, $cannotRemoveIOException),
            ],
            'target cannot be created' => [
                'filesystem' => $targetCannotBeCreatedFilesystem,
                'source' => $source,
                'target' => $target,
                'expected' => new TargetCreationException($target, $cannotCreateIOException),
            ],
            'source cannot be copied to target' => [
                'filesystem' => $sourceCannotBeMirrored,
                'source' => $source,
                'target' => $target,
                'expected' => new DuplicationException($source, $target, $cannotMirrorIOException),
            ],
        ];
    }
}
