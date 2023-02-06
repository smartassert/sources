<?php

declare(strict_types=1);

namespace App\Request;

use App\Entity\GitSource;
use App\Enum\Source\Type;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

class GitSourceRequest
{
    public const PARAMETER_HOST_URL = 'host-url';
    public const PARAMETER_PATH = 'path';
    public const PARAMETER_CREDENTIALS = 'credentials';

    #[Assert\Length(null, 1, GitSource::HOST_URL_MAX_LENGTH)]
    private string $hostUrl;
    private string $path;
    private string $credentials;

    public function __construct(Request $request)
    {
        $payload = $request->request;

        $this->hostUrl = trim((string) $payload->get(self::PARAMETER_HOST_URL));
        $this->path = trim((string) $payload->get(self::PARAMETER_PATH));
        $this->credentials = trim((string) $payload->get(self::PARAMETER_CREDENTIALS));
    }

    public function getType(): string
    {
        return Type::GIT->value;
    }

    public function getHostUrl(): string
    {
        return $this->hostUrl;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getCredentials(): string
    {
        return $this->credentials;
    }
}
