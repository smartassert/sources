<?php

declare(strict_types=1);

namespace App\Model;

readonly class DirectoryListing implements \JsonSerializable
{
    /**
     * @var File[]
     */
    public array $files;

    /**
     * @param File[] $files
     */
    public function __construct(array $files)
    {
        $this->files = $files;
    }

    /**
     * @return array<array{path: non-empty-string, size: int}>
     */
    public function jsonSerialize(): array
    {
        $data = [];

        foreach ($this->files as $file) {
            $data[] = $file->jsonSerialize();
        }

        return $data;
    }
}
