<?php

declare(strict_types=1);

namespace App\Model;

use App\Entity\GitSourceInterface;

class UserGitRepository implements DirectoryLocatorInterface, SourceRepositoryInterface
{
    public function __construct(
        private readonly string $id,
        private readonly GitSourceInterface $source
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getUserId(): string
    {
        return $this->source->getUserId();
    }

    public function getSource(): GitSourceInterface
    {
        return $this->source;
    }

    public function getRepositoryPath(): string
    {
        return $this->getDirectoryPath() . '/' . ltrim($this->source->getPath(), '/');
    }

    public function getDirectoryPath(): string
    {
        return sprintf(
            '%s/%s',
            $this->getUserId(),
            $this->getId(),
        );
    }
}
