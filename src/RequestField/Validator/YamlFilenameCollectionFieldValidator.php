<?php

declare(strict_types=1);

namespace App\RequestField\Validator;

use App\Exception\ErrorResponseException;
use App\Exception\ErrorResponseExceptionFactory;
use SmartAssert\ServiceRequest\Field\FieldInterface;
use SmartAssert\YamlFile\Filename;
use SmartAssert\YamlFile\Validator\YamlFilenameValidator;

readonly class YamlFilenameCollectionFieldValidator
{
    public function __construct(
        private YamlFilenameValidator $yamlFilenameValidator,
        private ErrorResponseExceptionFactory $exceptionFactory,
    ) {
    }

    /**
     * @return non-empty-string[]
     *
     * @throws ErrorResponseException
     */
    public function validate(FieldInterface $field): array
    {
        $names = $field->getValue();
        if (!is_array($names)) {
            throw $this->exceptionFactory->createForBadRequest($field, 'wrong_type');
        }

        $validatedNames = [];

        foreach ($names as $nameIndex => $name) {
            if (is_string($name)) {
                $validation = $this->yamlFilenameValidator->validate(Filename::parse($name));

                if ($validation->isValid() && '' !== $name) {
                    $validatedNames[] = $name;
                } else {
                    $field = $field->withErrorPosition($nameIndex + 1);

                    throw $this->exceptionFactory->createForBadRequest($field, 'invalid');
                }
            }
        }

        return $validatedNames;
    }
}
