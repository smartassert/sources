<?php

declare(strict_types=1);

namespace App\FooRequest;

use App\Exception\FooInvalidRequestException;
use App\FooRequest\Field\YamlFilenameCollectionField;
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
     * @throws FooInvalidRequestException
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
                $field->setErrorPosition($nameIndex + 1);

                throw new FooInvalidRequestException('invalid_request_field', $field, 'invalid');
            }
        }

        return $validatedNames;
    }
}
