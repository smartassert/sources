<?php

declare(strict_types=1);

namespace App\Tests\Unit\Validator;

use App\Model\Filename;
use App\Validator\FilenameValidator;
use PHPUnit\Framework\TestCase;

trait FileValidatorDataProviderTrait
{
    public function invalidSinglePartFilePath(): array
    {
        return [
            'single part, empty, not valid' => [
                'filename' => '',
                'expected' => false,
            ],
            'single part, contains backslash, not valid' => [
                'filename' => 'contains\\backslash',
                'expected' => false,
            ],
            'single part, contains null byte, not valid' => [
                'filename' => 'contains-null-byte' . chr(0),
                'expected' => false,
            ],
            'single part, contains space, not valid' => [
                'filename' => 'contains space',
                'expected' => false,
            ],
        ];
    }

    public function validSinglePartFilePath(): array
    {
        return [
            'single part, valid' => [
                'filename' => 'filename',
                'expected' => true,
            ],
        ];
    }

    public function invalidMultiplePartFilePath(): array
    {
        return [
            'multiple part, contains backslash, not valid' => [
                'filename' => 'contains\\backslash/part2/part3',
                'expected' => false,
            ],
            'multiple part, contains null byte, not valid' => [
                'filename' => 'part1/contains-null-byte' . chr(0) . '/part3',
                'expected' => false,
            ],
            'multiple part, contains space, not valid' => [
                'filename' => 'part1/contains space/part3',
                'expected' => false,
            ],
        ];
    }

    public function validMultiplePartFilePath(): array
    {
        return [
            'multiple part, valid' => [
                'filename' => 'path/to/file',
                'expected' => true,
            ],
        ];
    }
}
