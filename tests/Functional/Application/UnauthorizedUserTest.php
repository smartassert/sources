<?php

declare(strict_types=1);

namespace App\Tests\Functional\Application;

use App\Tests\Application\AbstractUnauthorizedUserTest;
use App\Tests\Services\ApplicationClient\AdapterInterface;
use App\Tests\Services\ApplicationClient\SymfonyAdapter;

class UnauthorizedUserTest extends AbstractUnauthorizedUserTest
{
    protected function getClientAdapter(): AdapterInterface
    {
        $adapter = self::getContainer()->get(SymfonyAdapter::class);
        \assert($adapter instanceof SymfonyAdapter);

        $adapter->setKernelBrowser($this->kernelBrowser);

        return $adapter;
    }
}
