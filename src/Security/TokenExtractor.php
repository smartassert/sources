<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\String\UnicodeString;

class TokenExtractor
{
    public function __construct(
        private string $headerKey,
        private string $valuePrefix,
    ) {
    }

    public function extract(Request $request): ?string
    {
        $headers = $request->headers;
        $authorizationHeader = $headers->get($this->headerKey);
        if (null === $authorizationHeader) {
            return null;
        }

        $authorizationHeaderString = new UnicodeString($authorizationHeader);
        if (false === $authorizationHeaderString->startsWith($this->valuePrefix)) {
            return null;
        }

        return (string) $authorizationHeaderString->trimPrefix($this->valuePrefix);
    }
}
