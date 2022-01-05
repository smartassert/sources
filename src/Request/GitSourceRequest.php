<?php

declare(strict_types=1);

namespace App\Request;

use Symfony\Component\HttpFoundation\Request;
use webignition\EncapsulatingRequestResolverBundle\Model\EncapsulatingRequestInterface;

class GitSourceRequest implements EncapsulatingRequestInterface
{
    public const KEY_POST_HOST_URL = 'host-url';
    public const KEY_POST_PATH = 'path';
    public const KEY_POST_CREDENTIALS = 'credentials';

    public function __construct(
        private string $hostUrl,
        private string $path,
        private ?string $credentials,
    ) {
    }

    public static function create(Request $request): GitSourceRequest
    {
        $credentials = $request->request->get(self::KEY_POST_CREDENTIALS);
        $credentials = is_string($credentials) || null === $credentials ? $credentials : null;

        return new GitSourceRequest(
            (string) $request->request->get(self::KEY_POST_HOST_URL),
            (string) $request->request->get(self::KEY_POST_PATH),
            $credentials
        );
    }

    public function getHostUrl(): string
    {
        return $this->hostUrl;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getCredentials(): ?string
    {
        return $this->credentials;
    }
}
