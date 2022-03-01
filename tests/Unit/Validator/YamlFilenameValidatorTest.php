<?php

declare(strict_types=1);

namespace App\Tests\Unit\Validator;

use App\Validator\YamlFilenameValidator;
use PHPUnit\Framework\TestCase;

class YamlFilenameValidatorTest extends TestCase
{
    use FileValidatorDataProviderTrait;

    private YamlFilenameValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new YamlFilenameValidator();
    }

    public function testIsNameValid(): void
    {
        self::assertFalse($this->validator->isNameValid(''));
        self::assertTrue($this->validator->isNameValid('non-empty string'));
    }

    public function testIsExtensionValid(): void
    {
        self::assertFalse($this->validator->isExtensionValid(''));
        self::assertFalse($this->validator->isExtensionValid('txt'));
        self::assertFalse($this->validator->isExtensionValid('yml'));
        self::assertTrue($this->validator->isExtensionValid('yaml'));
    }
}
