<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services;

use App\Services\StringCaseConverter;
use PHPUnit\Framework\TestCase;

class StringCaseConverterTest extends TestCase
{
    /**
     * @dataProvider convertDataProvider
     */
    public function testConvert(string $input, string $expected): void
    {
        $converter = new StringCaseConverter();

        self::assertSame($expected, $converter->convertCamelCaseToKebabCase($input));
    }

    /**
     * @return array<mixed>
     */
    public function convertDataProvider(): array
    {
        return [
            'empty' => [
                'input' => '',
                'expected' => '',
            ],
            'all lowercase' => [
                'input' => 'all lowercase',
                'expected' => 'all lowercase',
            ],
            'all uppercase' => [
                'input' => 'ALL UPPERCASE',
                'expected' => 'ALL UPPERCASE',
            ],
            'camelCase' => [
                'input' => 'camelCase',
                'expected' => 'camel-case',
            ],
            'multipleWordCamelCase' => [
                'input' => 'multipleWordCamelCase',
                'expected' => 'multiple-word-camel-case',
            ],
        ];
    }
}
