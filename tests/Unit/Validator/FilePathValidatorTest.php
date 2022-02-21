<?php

declare(strict_types=1);

namespace App\Tests\Unit\Validator;

use App\Validator\FilenameValidator;
use App\Validator\FilePathValidator;
use PHPUnit\Framework\TestCase;

class FilePathValidatorTest extends TestCase
{
    use FileValidatorDataProviderTrait;

    /**
     * @dataProvider invalidSinglePartFilePath
     * @dataProvider validSinglePartFilePath
     * @dataProvider invalidMultiplePartFilePath
     * @dataProvider validMultiplePartFilePath
     */
    public function testIsValid(string $path, bool $expected): void
    {
        $validator = new FilePathValidator(
            new FilenameValidator()
        );

        self::assertSame($expected, $validator->isValid($path));
    }
}
