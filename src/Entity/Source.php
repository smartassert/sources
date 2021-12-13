<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SourceRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=SourceRepository::class)
 */
class Source
{
    public const ID_LENGTH = 32;

    /**
     * @ORM\Id
     * @ORM\Column(type="string", length=Source::ID_LENGTH, unique=true)
     */
    protected string $id;

    /**
     * @ORM\Column(type="string", length=32)
     */
    private string $userId;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $hostUrl;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $path;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $accessToken;

    public function __construct(string $id, string $userId, string $hostUrl, string $path, ?string $accessToken)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->hostUrl = $hostUrl;
        $this->path = $path;
        $this->accessToken = $accessToken;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getHostUrl(): string
    {
        return $this->hostUrl;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }
}
