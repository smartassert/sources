<?php

declare(strict_types=1);

namespace App\Request;

use Symfony\Component\HttpFoundation\Request;
use webignition\EncapsulatingRequestResolverBundle\Model\EncapsulatingRequestInterface;

class CreateSourceRequest implements EncapsulatingRequestInterface
{
    public const KEY_ATTRIBUTE_USER_ID = 'userId';
    public const KEY_POST_HOST_URL = 'host-url';
    public const KEY_POST_PATH = 'path';
    public const KEY_POST_ACCESS_TOKEN = 'access-token';

    public function __construct(
        private string $userId,
        private string $hostUrl,
        private string $path,
        private ?string $accessToken
    ) {
    }

    public static function create(Request $request): CreateSourceRequest
    {
        $userId = $request->attributes->get(self::KEY_ATTRIBUTE_USER_ID);
        $userId = is_string($userId) ? $userId : '';

        $accessToken = $request->request->get(self::KEY_POST_ACCESS_TOKEN);
        $accessToken = is_string($accessToken) || null === $accessToken ? $accessToken : null;

        return new CreateSourceRequest(
            $userId,
            (string) $request->request->get(self::KEY_POST_HOST_URL),
            (string) $request->request->get(self::KEY_POST_PATH),
            $accessToken
        );
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
