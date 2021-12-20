<?php

declare(strict_types=1);

namespace App\Request;

use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use webignition\EncapsulatingRequestResolverBundle\Model\EncapsulatingRequestInterface;

class CreateGitSourceRequest implements EncapsulatingRequestInterface
{
    public const KEY_POST_HOST_URL = 'host-url';
    public const KEY_POST_PATH = 'path';
    public const KEY_POST_ACCESS_TOKEN = 'access-token';
    public const KEY_POST_REF = 'ref';

    public function __construct(
        private string $hostUrl,
        private string $path,
        private ?string $accessToken,
        private ?string $ref
    ) {
    }

    public static function create(Request $request): CreateGitSourceRequest
    {
        $accessToken = self::getStringValueOrNull($request->request, self::KEY_POST_ACCESS_TOKEN);
        $ref = self::getStringValueOrNull($request->request, self::KEY_POST_REF);

        return new CreateGitSourceRequest(
            (string) $request->request->get(self::KEY_POST_HOST_URL),
            (string) $request->request->get(self::KEY_POST_PATH),
            $accessToken,
            $ref
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

    public function getRef(): ?string
    {
        return $this->ref;
    }

    private static function getStringValueOrNull(ParameterBag $parameterBag, string $key): ?string
    {
        $value = $parameterBag->get($key);

        return is_string($value) || null === $value ? $value : null;
    }
}
