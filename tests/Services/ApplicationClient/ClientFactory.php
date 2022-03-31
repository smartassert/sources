<?php

declare(strict_types=1);

namespace App\Tests\Services\ApplicationClient;

use Symfony\Component\Routing\RouterInterface;

class ClientFactory
{
    public function __construct(
        private RouterInterface $router,
    ) {
    }

    public function create(ClientInterface $adapter): Client
    {
        return new Client($adapter, $this->router);
    }
}
