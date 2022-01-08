<?php

declare(strict_types=1);

namespace App\Tests\Unit\Model;

use App\Model\CommandDefinition;
use PHPUnit\Framework\TestCase;

class CommandDefinitionTest extends TestCase
{
    /**
     * @dataProvider buildDataProvider
     */
    public function testBuild(CommandDefinition $commandDefinition, string $expected): void
    {
        self::assertSame($expected, $commandDefinition->build());
    }

    /**
     * @return array<mixed>
     */
    public function buildDataProvider(): array
    {
        return [
            'no parameters' => [
                'commandDefinition' => new CommandDefinition('./command'),
                'expected' => './command',
            ],
            'has parameters' => [
                'commandDefinition' => new CommandDefinition('./command %param1% %param2%', [
                    '%param1%' => 'first parameter value',
                    '%param2%' => 'second parameter value',
                ]),
                'expected' => "./command 'first parameter value' 'second parameter value'",
            ],
            'has parameters with required escaping' => [
                'commandDefinition' => new CommandDefinition('./command %param1% %param2%', [
                    '%param1%' => "'first' parameter value",
                    '%param2%' => "'second' parameter value",
                ]),
                'expected' => "./command '\\'first\\' parameter value' '\\'second\\' parameter value'",
            ],
        ];
    }
}
