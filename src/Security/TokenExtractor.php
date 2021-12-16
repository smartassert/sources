<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\String\UnicodeString;

class TokenExtractor
{
    public function __construct(
        private AuthorizationRequestProperties $authorizationRequestProperties,
    ) {
    }

    public function extract(Request $request): ?string
    {
        $headers = $request->headers;
        $authorizationHeader = $headers->get($this->authorizationRequestProperties->getHeaderKey());
        if (null === $authorizationHeader) {
            return null;
        }

        $valuePrefix = $this->authorizationRequestProperties->getValuePrefix();

        $authorizationHeaderString = new UnicodeString($authorizationHeader);
        if (false === $authorizationHeaderString->startsWith($valuePrefix)) {
            return null;
        }

        return (string) $authorizationHeaderString->trimPrefix($valuePrefix);
    }
}
