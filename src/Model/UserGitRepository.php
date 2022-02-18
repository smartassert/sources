<?php

declare(strict_types=1);

namespace App\Model;

use App\Entity\GitSource;

class UserGitRepository implements UserFileLocatorInterface
{
    use UserSourceFileLocatorTrait;

    private string $id;
    private ?string $absolutePath = null;

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

    public function getAbsolutePath(): ?string
    {
        return $this->absolutePath;
    }

    public function withAbsolutePath(string $absolutePath): self
    {
        $new = clone $this;
        $new->absolutePath = $absolutePath;

        return $new;
    }
}
