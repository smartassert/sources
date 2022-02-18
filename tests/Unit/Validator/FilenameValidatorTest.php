<?php

declare(strict_types=1);

namespace App\Tests\Unit\Validator;

use App\Model\Filename;
use App\Validator\FilenameValidator;
use PHPUnit\Framework\TestCase;

class FilenameValidatorTest extends TestCase
{
    /**
     * @dataProvider isValidDataProvider
     */
    public function testIsValid(string $filename, bool $expected): void
    {
        self::assertSame($expected, (new FilenameValidator())->isValid($filename));
    }

    /**
     * @return array<mixed>
     */
    public function isValidDataProvider(): array
    {
        return [
            'empty, not valid' => [
                'filename' => '',
                'expected' => false,
            ],
            'contains backslash, not valid' => [
                'filename' => 'contains\\backslash',
                'expected' => false,
            ],
            'contains null byte, not valid' => [
                'filename' => 'contains-null-byte' . chr(0),
                'expected' => false,
            ],
            'contains space, not valid' => [
                'filename' => 'contains space',
                'expected' => false,
            ],
            'valid' => [
                'filename' => 'filename',
                'expected' => true,
            ],
        ];
    }
}
