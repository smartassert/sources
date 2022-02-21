<?php

declare(strict_types=1);

namespace App\Model;

use Traversable;

/**
 * @implements \IteratorAggregate<int|string, string>
 */
class SourceFileCollection implements \IteratorAggregate
{
    /**
     * @param string[] $paths
     */
    public function __construct(
        private array $paths,
        public readonly string $pathPrefix,
    ) {
    }

    /**
     * @return Traversable<int|string, string>
     */
    public function getIterator(): Traversable
    {
        return new \ArrayIterator($this->paths);
    }
}
