<?php

declare(strict_types=1);

namespace App\Validator;

use App\Model\YamlFilename;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class YamlFilenameConstraintValidator extends ConstraintValidator
{
    public function __construct(
        private readonly FilenameValidator $filenameValidator,
        private readonly YamlFilenameValidator $yamlFilenameValidator,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof YamlFilenameConstraint) {
            throw new UnexpectedTypeException($constraint, YamlFileConstraint::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!$value instanceof YamlFilename) {
            throw new UnexpectedValueException($value, YamlFilename::class);
        }

        if (false === $this->filenameValidator->isValid($value->getValue())) {
            $this->context->buildViolation($constraint::MESSAGE_FILENAME_INVALID)
                ->atPath('name')
                ->addViolation()
            ;
        }

        if (false === $this->yamlFilenameValidator->isNameValid($value->getName())) {
            $this->context->buildViolation($constraint::MESSAGE_NAME_EMPTY)
                ->atPath('name')
                ->addViolation()
            ;
        }

        if (false === $this->yamlFilenameValidator->isExtensionValid($value->getExtension())) {
            $this->context->buildViolation($constraint::MESSAGE_EXTENSION_INVALID)
                ->atPath('name')
                ->addViolation()
            ;
        }
    }
}
