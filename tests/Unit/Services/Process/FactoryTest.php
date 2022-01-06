<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services\Process;

use App\Services\Process\Factory;
use PHPUnit\Framework\TestCase;

class FactoryTest extends TestCase
{
    public function testCreate(): void
    {
        $command = './command arg1 arg2 arg3';
        $factory = new Factory();

        $process = $factory->create($command);

        self::assertSame(
            $command,
            $process->getCommandLine()
        );
    }
}
