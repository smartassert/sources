<?php

declare(strict_types=1);

namespace App\RequestField\Validator;

use App\Exception\InvalidRequestException;
use App\RequestField\Field\YamlFilenameField;
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
            throw new InvalidRequestException($field, 'invalid');
        }

        return $filename;
    }
}
