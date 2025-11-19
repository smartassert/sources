<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\EntityType;
use App\Enum\Source\Type;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class GitSource extends AbstractSource implements GitSourceInterface
{
    public const HOST_URL_MAX_LENGTH = 255;
    public const PATH_MAX_LENGTH = 255;
    public const CREDENTIALS_MAX_LENGTH = 255;

    #[ORM\Column(type: 'string', length: self::HOST_URL_MAX_LENGTH)]
    private string $hostUrl;

    #[ORM\Column(type: 'string', length: self::PATH_MAX_LENGTH)]
    private string $path = '/';

    #[ORM\Column(type: 'string', length: self::CREDENTIALS_MAX_LENGTH)]
    private string $credentials = '';

    /**
     * @return non-empty-string
     */
    public function getHostUrl(): string
    {
        \assert('' !== $this->hostUrl);

        return $this->hostUrl;
    }

    /**
     * @param non-empty-string $hostUrl
     */
    public function setHostUrl(string $hostUrl): void
    {
        $this->hostUrl = $hostUrl;
    }

    /**
     * @param non-empty-string $path
     */
    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    public function setCredentials(string $credentials): void
    {
        $this->credentials = $credentials;
    }

    /**
     * @return non-empty-string
     */
    public function getPath(): string
    {
        \assert('' !== $this->path);

        return $this->path;
    }

    public function getCredentials(): string
    {
        return $this->credentials;
    }

    public function getRunParameterNames(): array
    {
        return [
            'ref',
        ];
    }

    /**
     * @return array{
     *     "id": string,
     *     "user_id": non-empty-string,
     *     "type": non-empty-string,
     *     "label": non-empty-string,
     *     "host_url": string,
     *     "path": string,
     *     "has_credentials": bool
     * }
     */
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'host_url' => $this->hostUrl,
            'path' => $this->path,
            'has_credentials' => '' !== $this->credentials,
        ]);
    }

    public function getType(): Type
    {
        return Type::GIT;
    }

    public function getIdentifier(): EntityIdentifierInterface
    {
        return new EntityIdentifier($this->getId(), EntityType::GIT_SOURCE->value);
    }
}
