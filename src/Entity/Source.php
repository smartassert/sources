<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SourceRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=SourceRepository::class)
 */
class Source implements \JsonSerializable
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
     * @ORM\ManyToOne(targetEntity=SourceType::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private SourceType $type;

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

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $ref;

    public function __construct(
        string $id,
        string $userId,
        SourceType $type,
        string $hostUrl,
        string $path,
        ?string $accessToken = null,
        ?string $ref = null
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->type = $type;
        $this->hostUrl = $hostUrl;
        $this->path = $path;
        $this->accessToken = $accessToken;
        $this->ref = $ref;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getType(): SourceType
    {
        return $this->type;
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

    public function getRef(): ?string
    {
        return $this->ref;
    }

    /**
     * @return array{
     *     "id": string,
     *     "user_id": string,
     *     "type": SourceType::TYPE_*,
     *     "host_url": string,
     *     "path": string,
     *     "access_token": string|null,
     *     "ref": string|null
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'type' => $this->type->getName(),
            'host_url' => $this->hostUrl,
            'path' => $this->path,
            'access_token' => $this->accessToken,
            'ref' => $this->ref,
        ];
    }
}
