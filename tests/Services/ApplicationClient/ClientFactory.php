<?php

declare(strict_types=1);

namespace App\Tests\Services\ApplicationClient;

class ClientFactory
{
    public function __construct(
        private Routes $routes,
    ) {
    }

    public function create(AdapterInterface $adapter): Client
    {
        return new Client($adapter, $this->routes);
    }
}
