<?php

declare(strict_types=1);

namespace App\Services\SourceRepository\Reader;

use App\Model\SourceRepositoryInterface;
use App\Model\UserGitRepository;
use League\Flysystem\FilesystemReader;

class UserGitRepositoryReader implements ReaderInterface
{
    public function __construct(
        private FilesystemReader $reader,
    ) {
    }

    public function handles(SourceRepositoryInterface $sourceRepository): bool
    {
        return $sourceRepository instanceof UserGitRepository;
    }

    public function getReader(): FilesystemReader
    {
        return $this->reader;
    }
}
