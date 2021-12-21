<?php

declare(strict_types=1);

namespace App\Request;

use Symfony\Component\HttpFoundation\Request;

class UpdateGitSourceRequest
{
    public const KEY_POST_HOST_URL = 'host-url';
    public const KEY_POST_PATH = 'path';
    public const KEY_POST_ACCESS_TOKEN = 'access-token';

    public function __construct(
        private string $hostUrl,
        private string $path,
        private ?string $accessToken,
    ) {
    }

    public static function create(Request $request): UpdateGitSourceRequest
    {
        $accessToken = $request->request->get(self::KEY_POST_ACCESS_TOKEN);
        $accessToken = is_string($accessToken) || null === $accessToken ? $accessToken : null;

        return new UpdateGitSourceRequest(
            (string) $request->request->get(self::KEY_POST_HOST_URL),
            (string) $request->request->get(self::KEY_POST_PATH),
            $accessToken
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

    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }
}
