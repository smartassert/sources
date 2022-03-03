<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Tests\Services\ApplicationClient\AdapterInterface;
use App\Tests\Services\ApplicationClient\HttpAdapter;

trait GetClientAdapterTrait
{
    protected function getClientAdapter(): AdapterInterface
    {
        $adapter = self::getContainer()->get(HttpAdapter::class);
        \assert($adapter instanceof AdapterInterface);

        return $adapter;
    }
}
