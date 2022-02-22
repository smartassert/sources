<?php

declare(strict_types=1);

namespace App\Model;

use App\Entity\GitSource;

class UserGitRepository implements UserFileLocatorInterface, SerializableSourceInterface
{
    private string $id;

    public function __construct(private GitSource $source)
    {
        $this->id = EntityId::create();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUserId(): string
    {
        return $this->source->getUserId();
    }

    public function getSource(): GitSource
    {
        return $this->source;
    }

    public function getSerializableSourcePath(): string
    {
        return $this->source->getPath();
    }

    public function getFilePath(): string
    {
        return sprintf(
            '%s/%s',
            $this->getUserId(),
            $this->getId(),
        );
    }
}
