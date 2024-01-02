<?php

declare(strict_types=1);

namespace App\RequestField\Validator;

use App\ErrorResponse\BadRequestError;
use App\Exception\BadRequestException;
use SmartAssert\ServiceRequest\Field\FieldInterface;
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
    public function validate(FieldInterface $field): array
    {
        $names = $field->getValue();
        if (!is_array($names)) {
            throw new BadRequestException(new BadRequestError($field, 'wrong_type'));
        }

        $validatedNames = [];

        foreach ($names as $nameIndex => $name) {
            if (is_string($name)) {
                $validation = $this->yamlFilenameValidator->validate(Filename::parse($name));

                if ($validation->isValid() && '' !== $name) {
                    $validatedNames[] = $name;
                } else {
                    $field = $field->withErrorPosition($nameIndex + 1);

                    throw new BadRequestException(new BadRequestError($field, 'invalid'));
                }
            }
        }

        return $validatedNames;
    }
}
