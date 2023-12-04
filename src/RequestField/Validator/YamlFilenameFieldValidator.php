<?php

declare(strict_types=1);

namespace App\RequestField\Validator;

use App\Exception\BadRequestException;
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
     * @throws BadRequestException
     */
    public function validate(YamlFilenameField $field): YamlFilename
    {
        $filename = $field->getFilename();
        $validation = $this->yamlFilenameValidator->validate($filename);

        if (false === $validation->isValid()) {
            throw new BadRequestException($field, 'invalid');
        }

        return $filename;
    }
}
