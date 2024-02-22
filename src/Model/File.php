<?php

declare(strict_types=1);

namespace App\Model;

readonly class File implements \JsonSerializable
{
    /**
     * @param non-empty-string $path
     */
    public function __construct(
        private string $path,
        private int $size,
    ) {
    }

    /**
     * @return array{path: non-empty-string, size: int}
     */
    public function jsonSerialize(): array
    {
        return [
            'path' => $this->path,
            'size' => $this->size,
        ];
    }
}
