<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\Source\Type;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class GitSource extends AbstractOriginSource implements SourceOriginInterface, \JsonSerializable
{
    public const HOST_URL_MAX_LENGTH = 255;
    public const PATH_MAX_LENGTH = 255;
    public const CREDENTIALS_MAX_LENGTH = 255;

    /**
     * @var non-empty-string
     */
    #[ORM\Column(type: 'string', length: self::HOST_URL_MAX_LENGTH)]
    private string $hostUrl;

    /**
     * @var non-empty-string
     */
    #[ORM\Column(type: 'string', length: self::PATH_MAX_LENGTH)]
    private string $path;

    #[ORM\Column(type: 'string', length: self::CREDENTIALS_MAX_LENGTH)]
    private string $credentials;

    /**
     * @param non-empty-string $id
     * @param non-empty-string $userId
     * @param non-empty-string $label
     * @param non-empty-string $hostUrl
     * @param non-empty-string $path
     */
    public function __construct(
        string $id,
        string $userId,
        string $label,
        string $hostUrl,
        string $path = '/',
        string $credentials = ''
    ) {
        parent::__construct($id, $userId, $label);

        $this->hostUrl = $hostUrl;
        $this->path = $path;
        $this->credentials = $credentials;
    }

    /**
     * @return non-empty-string
     */
    public function getHostUrl(): string
    {
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
        return $this->path;
    }

    public function getCredentials(): string
    {
        return $this->credentials;
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
     *     "user_id": non-empty-string,
     *     "type": 'git',
     *     "label": non-empty-string,
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
            'label' => $this->getLabel(),
            'host_url' => $this->hostUrl,
            'path' => $this->path,
            'has_credentials' => '' !== $this->credentials
        ];
    }
}
