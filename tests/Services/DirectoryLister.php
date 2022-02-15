<?php

declare(strict_types=1);

namespace App\Tests\Services;

class DirectoryLister
{
    /**
     * @return \SplFileInfo[]
     */
    public function list(\RecursiveDirectoryIterator $directoryIterator, ?string $basePath = null): array
    {
        $basePath = is_string($basePath) ? $basePath : $directoryIterator->getPath();
        $pathNames = [];

        foreach ($directoryIterator as $item) {
            if ($item instanceof \SplFileInfo && $item->isFile()) {
                $fileRelativePath = $this->removeBasePathFromPath($basePath, $item->getPathname());

                $pathNames[$fileRelativePath] = $item;
            }

            if ($item instanceof \SplFileInfo && $item->isDir() && !$directoryIterator->isDot()) {
                $children = $directoryIterator->getChildren();
                if ($children instanceof \RecursiveDirectoryIterator) {
                    $pathNames = array_merge($pathNames, $this->list($children, $basePath));
                }
            }
        }

        return $pathNames;
    }

    private function removeBasePathFromPath(string $basePath, string $path): string
    {
        $basePathToRemove = $basePath . '/';
        if (str_starts_with($path, $basePathToRemove)) {
            $path = substr($path, strlen($basePathToRemove));
        }

        return $path;
    }
}
