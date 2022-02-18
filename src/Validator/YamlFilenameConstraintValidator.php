<?php

declare(strict_types=1);

namespace App\Validator;

use App\Model\Filename;
use App\Model\YamlFile;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class YamlFilenameConstraintValidator extends ConstraintValidator
{
    public function __construct(
        private readonly FilenameValidator $filenameValidator,
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

        if (false === $this->filenameValidator->isValid($value->getValue())) {
            $this->context->buildViolation($constraint::MESSAGE_FILENAME_INVALID)
                ->atPath('name')
                ->addViolation()
            ;
        }

        if ('' === $value->getName()) {
            $this->context->buildViolation($constraint::MESSAGE_NAME_EMPTY)
                ->atPath('name')
                ->addViolation()
            ;
        }

        if (!in_array($value->getExtension(), YamlFile::EXTENSIONS)) {
            $this->context->buildViolation($constraint::MESSAGE_EXTENSION_INVALID)
                ->atPath('name')
                ->addViolation()
            ;
        }
    }
}
