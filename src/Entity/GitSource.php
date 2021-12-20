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
    private ?string $accessToken;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $ref;

    public function __construct(
        string $id,
        string $userId,
        string $hostUrl,
        string $path = '/',
        ?string $accessToken = null,
        ?string $ref = null
    ) {
        parent::__construct($id, $userId);

        $this->hostUrl = $hostUrl;
        $this->path = $path;
        $this->accessToken = $accessToken;
        $this->ref = $ref;
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
     *     "type": 'git',
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
            'user_id' => $this->getUserId(),
            'type' => SourceTypeInterface::TYPE_GIT,
            'host_url' => $this->hostUrl,
            'path' => $this->path,
            'access_token' => $this->accessToken,
            'ref' => $this->ref,
        ];
    }
}
