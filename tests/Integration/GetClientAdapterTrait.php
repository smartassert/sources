<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Tests\Services\ApplicationClient\ClientInterface;
use App\Tests\Services\ApplicationClient\HttpClient;

trait GetClientAdapterTrait
{
    protected function getClientAdapter(): ClientInterface
    {
        $adapter = self::getContainer()->get(HttpClient::class);
        \assert($adapter instanceof ClientInterface);

        return $adapter;
    }
}
