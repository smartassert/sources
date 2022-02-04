<?php

declare(strict_types=1);

namespace App\Tests\Services;

class FixtureLoader
{
    public function __construct(
        private string $fixturesBasePath,
    ) {
    }

    public function load(string $path): string
    {
        return trim((string) file_get_contents($this->fixturesBasePath . $path));
    }
}
