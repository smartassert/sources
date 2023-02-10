<?php

declare(strict_types=1);

namespace App\Model;

class DirectoryListing implements \JsonSerializable
{
    /**
     * @var non-empty-string[]
     */
    public readonly array $paths;

    /**
     * @param non-empty-string[] $paths
     */
    public function __construct(array $paths)
    {
        $this->paths = $this->sort($paths);
    }

    /**
     * @return non-empty-string[]
     */
    public function jsonSerialize(): array
    {
        return $this->paths;
    }

    /**
     * @param non-empty-string[] $paths
     *
     * @return non-empty-string[]
     */
    private function sort(array $paths): array
    {
        sort($paths);

        $rootDirectoryPaths = [];
        $nonRootDirectoryPaths = [];

        foreach ($paths as $path) {
            if (!str_contains($path, '/')) {
                $rootDirectoryPaths[] = $path;
            } else {
                $nonRootDirectoryPaths[] = $path;
            }
        }

        return array_merge($nonRootDirectoryPaths, $rootDirectoryPaths);
    }
}
