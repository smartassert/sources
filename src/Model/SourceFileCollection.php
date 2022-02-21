<?php

declare(strict_types=1);

namespace App\Model;

use Traversable;

/**
 * @implements \IteratorAggregate<int|string, SourceFile>
 */
class SourceFileCollection implements \IteratorAggregate
{
    /**
     * @param SourceFile[] $files
     */
    public function __construct(
        private array $files,
        public readonly string $pathPrefix,
    ) {
    }

    /**
     * @return Traversable<int|string, SourceFile>
     */
    public function getIterator(): Traversable
    {
        return new \ArrayIterator($this->files);
    }
}
