<?php

declare(strict_types=1);

namespace App\Tests\Unit\Model;

use App\Model\Filename;
use PHPUnit\Framework\TestCase;

class FilenameTest extends TestCase
{
    /**
     * @dataProvider isValidDataProvider
     */
    public function testIsValid(Filename $filename, bool $expectedIsValid): void
    {
        self::assertSame($expectedIsValid, $filename->isValid());
    }

    /**
     * @return array<mixed>
     */
    public function isValidDataProvider(): array
    {
        return [
            'empty string is not valid' => [
                'filename' => new Filename(''),
                'expectedIsValid' => false,
            ],
            'whitespace-only is not valid' => [
                'filename' => new Filename(" \t\n\r"),
                'expectedIsValid' => false,
            ],
            'containing back slash is not valid' => [
                'filename' => new Filename('one two \\ three'),
                'expectedIsValid' => false,
            ],
            'containing nul byte is not valid' => [
                'filename' => new Filename('one two ' . chr(0) . ' three'),
                'expectedIsValid' => false,
            ],
            'valid' => [
                'filename' => new Filename('valid filename'),
                'expectedIsValid' => true,
            ],
        ];
    }
}
