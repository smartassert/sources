<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services\Process;

use App\Services\Process\CommandBuilder;
use PHPUnit\Framework\TestCase;

class CommandBuilderTest extends TestCase
{
    /**
     * @dataProvider buildDataProvider
     *
     * @param array<string, string> $parameters
     */
    public function testBuild(string $command, array $parameters, string $expected): void
    {
        $builder = new CommandBuilder();

        self::assertSame(
            $expected,
            $builder->build($command, $parameters)
        );
    }

    /**
     * @return array<mixed>
     */
    public function buildDataProvider(): array
    {
        return [
            'no parameters' => [
                'command' => './command',
                'parameters' => [],
                'expected' => './command',
            ],
            'has parameters' => [
                'command' => './command %param1% %param2%',
                'parameters' => [
                    '%param1%' => 'first parameter value',
                    '%param2%' => 'second parameter value',
                ],
                'expected' => "./command 'first parameter value' 'second parameter value'",
            ],
            'has parameters with required escaping' => [
                'command' => './command %param1% %param2%',
                'parameters' => [
                    '%param1%' => "'first' parameter value",
                    '%param2%' => "'second' parameter value",
                ],
                'expected' => "./command '\\'first\\' parameter value' '\\'second\\' parameter value'",
            ],
        ];
    }
}
