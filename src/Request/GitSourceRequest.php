<?php

declare(strict_types=1);

namespace App\Request;

class GitSourceRequest
{
    public const PARAMETER_LABEL = 'label';
    public const PARAMETER_HOST_URL = 'host-url';
    public const PARAMETER_PATH = 'path';
    public const PARAMETER_CREDENTIALS = 'credentials';

    public function __construct(
        public readonly string $label,
        public readonly string $hostUrl,
        public readonly string $path,
        public readonly string $credentials,
    ) {
    }
}
