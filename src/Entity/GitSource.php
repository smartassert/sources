<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class GitSource extends AbstractSource implements \JsonSerializable
{
    #[ORM\Column(type: 'string', length: 255)]
    private string $hostUrl;

    #[ORM\Column(type: 'string', length: 255)]
    private string $path;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $credentials;

    public function __construct(string $userId, string $hostUrl, string $path = '/', ?string $credentials = null)
    {
        parent::__construct($userId);

        $this->hostUrl = $hostUrl;
        $this->path = $path;
        $this->credentials = $credentials;
    }

    public function getHostUrl(): string
    {
        return $this->hostUrl;
    }

    public function setHostUrl(string $hostUrl): void
    {
        $this->hostUrl = $hostUrl;
    }

    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    public function setCredentials(?string $credentials): void
    {
        $this->credentials = $credentials;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getCredentials(): ?string
    {
        return $this->credentials;
    }

    /**
     * @return SourceInterface::TYPE_GIT
     */
    public function getType(): string
    {
        return SourceInterface::TYPE_GIT;
    }

    /**
     * @return array{
     *     "id": string,
     *     "user_id": string,
     *     "type": SourceInterface::TYPE_GIT,
     *     "host_url": string,
     *     "path": string,
     *     "has_credentials": bool
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->getUserId(),
            'type' => $this->getType(),
            'host_url' => $this->hostUrl,
            'path' => $this->path,
            'has_credentials' => is_string($this->credentials)
        ];
    }
}
