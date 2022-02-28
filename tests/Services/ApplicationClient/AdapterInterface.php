<?php

declare(strict_types=1);

namespace App\Tests\Services\ApplicationClient;

use Psr\Http\Message\ResponseInterface;

interface AdapterInterface
{
    /**
     * @param array<string, string> $headers
     */
    public function makeRequest(
        string $method,
        string $uri,
        array $headers = [],
        ?string $body = null
    ): ResponseInterface;
}
