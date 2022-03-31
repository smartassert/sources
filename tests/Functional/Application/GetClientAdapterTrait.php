<?php

declare(strict_types=1);

namespace App\Tests\Functional\Application;

use App\Tests\Services\ApplicationClient\ClientInterface;
use App\Tests\Services\ApplicationClient\SymfonyClient;

trait GetClientAdapterTrait
{
    protected function getClientAdapter(): ClientInterface
    {
        $adapter = self::getContainer()->get(SymfonyClient::class);
        \assert($adapter instanceof SymfonyClient);

        $adapter->setKernelBrowser($this->kernelBrowser);

        return $adapter;
    }
}
