<?php

declare(strict_types=1);

namespace App\RequestField\Validator;

use App\Exception\BadRequestException;
use App\RequestField\Field\YamlFilenameCollectionField;
use SmartAssert\YamlFile\Filename;
use SmartAssert\YamlFile\Validator\YamlFilenameValidator;

readonly class YamlFilenameCollectionFieldValidator
{
    public function __construct(
        private YamlFilenameValidator $yamlFilenameValidator,
    ) {
    }

    /**
     * @return non-empty-string[]
     *
     * @throws BadRequestException
     */
    public function validate(YamlFilenameCollectionField $field): array
    {
        $names = $field->getValue();
        $validatedNames = [];

        foreach ($names as $nameIndex => $name) {
            $validation = $this->yamlFilenameValidator->validate(Filename::parse($name));

            if ($validation->isValid() && '' !== $name) {
                $validatedNames[] = $name;
            } else {
                $field = $field->withErrorPosition($nameIndex + 1);

                throw new BadRequestException($field, 'invalid');
            }
        }

        return $validatedNames;
    }
}
