<?php

declare(strict_types=1);

namespace App\FooRequest;

use App\Exception\InvalidRequestException;
use App\FooRequest\Field\YamlFilenameField;
use SmartAssert\YamlFile\Filename as YamlFilename;
use SmartAssert\YamlFile\Validator\YamlFilenameValidator;

readonly class YamlFilenameFieldValidator
{
    public function __construct(
        private YamlFilenameValidator $yamlFilenameValidator,
    ) {
    }

    /**
     * @throws InvalidRequestException
     */
    public function validate(YamlFilenameField $field): YamlFilename
    {
        $filename = $field->getFilename();
        $validation = $this->yamlFilenameValidator->validate($filename);

        if (false === $validation->isValid()) {
            throw new InvalidRequestException('invalid_request_field', $field, 'invalid');
        }

        return $filename;
    }
}
