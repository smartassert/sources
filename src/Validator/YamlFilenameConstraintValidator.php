<?php

declare(strict_types=1);

namespace App\Validator;

use SmartAssert\YamlFile\Filename;
use SmartAssert\YamlFile\Validator\YamlFilenameValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class YamlFilenameConstraintValidator extends ConstraintValidator
{
    public function __construct(
        private readonly YamlFilenameValidator $validator,
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

        if (!$value instanceof Filename) {
            throw new UnexpectedValueException($value, Filename::class);
        }

        $filenameValidation = $this->validator->validate($value);
        if (false === $filenameValidation->isValid()) {
            $this->context->buildViolation($constraint::MESSAGE_NAME_INVALID)
                ->atPath('name')
                ->addViolation()
            ;
        }
    }
}
