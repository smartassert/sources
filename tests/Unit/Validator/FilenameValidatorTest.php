<?php

declare(strict_types=1);

namespace App\Tests\Unit\Validator;

use App\Validator\FilenameValidator;
use PHPUnit\Framework\TestCase;

class FilenameValidatorTest extends TestCase
{
    use FileValidatorDataProviderTrait;

    /**
     * @dataProvider invalidSinglePartFilePath
     * @dataProvider validSinglePartFilePath
     */
    public function testIsValid(string $filename, bool $expected): void
    {
        self::assertSame($expected, (new FilenameValidator())->isValid($filename));
    }
}
