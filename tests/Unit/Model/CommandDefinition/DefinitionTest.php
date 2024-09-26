<?php

declare(strict_types=1);

namespace App\Tests\Unit\Model\CommandDefinition;

use App\Model\CommandDefinition\Definition;
use App\Model\CommandDefinition\Option;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DefinitionTest extends TestCase
{
    #[DataProvider('buildDataProvider')]
    public function testBuild(Definition $definition, string $expected): void
    {
        self::assertSame($expected, $definition->build());
    }

    /**
     * @return array<mixed>
     */
    public static function buildDataProvider(): array
    {
        return [
            'no options, no arguments' => [
                'definition' => new Definition('./command'),
                'expected' => './command',
            ],
            'has options, no arguments' => [
                'definition' => (new Definition('./command'))
                    ->withOptions([
                        Option::createShort('s'),
                        Option::createLong('long')
                    ]),
                'expected' => './command -s --long',
            ],
            'no options, has arguments' => [
                'definition' => (new Definition('./command'))
                    ->withArguments([
                        'one',
                        'two',
                    ]),
                'expected' => "./command 'one' 'two'",
            ],
            'no options, has arguments containing single quotes' => [
                'definition' => (new Definition('./command'))
                    ->withArguments([
                        "o'n'e",
                        'two',
                    ]),
                'expected' => "./command 'o\\'n\\'e' 'two'",
            ],
            'has options, has arguments' => [
                'definition' => (new Definition('./command'))
                    ->withOptions([
                        Option::createShort('s'),
                        Option::createLong('long')
                    ])->withArguments([
                        'one',
                        'two',
                    ]),
                'expected' => "./command -s --long 'one' 'two'",
            ],
        ];
    }
}
