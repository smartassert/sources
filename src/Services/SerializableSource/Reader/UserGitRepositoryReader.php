<?php

declare(strict_types=1);

namespace App\Services\SerializableSource\Reader;

use App\Model\SerializableSourceInterface;
use App\Model\UserGitRepository;
use League\Flysystem\FilesystemReader;

class UserGitRepositoryReader implements ReaderInterface
{
    public function __construct(
        private FilesystemReader $reader,
    ) {
    }

    public function handles(SerializableSourceInterface $serializableSource): bool
    {
        return $serializableSource instanceof UserGitRepository;
    }

    public function getReader(): FilesystemReader
    {
        return $this->reader;
    }
}
