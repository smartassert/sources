<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\Source\Type;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class GitSource extends AbstractSource implements SourceOriginInterface, \JsonSerializable
{
    #[ORM\Column(type: 'string', length: 255)]
    private string $hostUrl;

    #[ORM\Column(type: 'string', length: 255)]
    private string $path;

    #[ORM\Column(type: 'string', length: 255)]
    private string $credentials;

    public function __construct(string $userId, string $hostUrl, string $path = '/', string $credentials = '')
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

    public function setCredentials(string $credentials): void
    {
        $this->credentials = $credentials;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getCredentials(): string
    {
        return $this->credentials;
    }

    public function getType(): Type
    {
        return Type::GIT;
    }

    public function getRunParameterNames(): array
    {
        return [
            'ref'
        ];
    }

    /**
     * @return array{
     *     "id": string,
     *     "user_id": string,
     *     "type": 'git',
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
            'type' => Type::GIT->value,
            'host_url' => $this->hostUrl,
            'path' => $this->path,
            'has_credentials' => '' !== $this->credentials
        ];
    }
}
