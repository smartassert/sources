<?php

declare(strict_types=1);

namespace App\Validator;

use App\Model\Filename;
use App\Model\YamlFile;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class FilenameConstraintValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof FilenameConstraint) {
            throw new UnexpectedTypeException($constraint, YamlFileConstraint::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!$value instanceof Filename) {
            throw new UnexpectedValueException($value, Filename::class);
        }

        if (
            !$value->isValid()
            || '' === $value->getName()
            || !in_array($value->getExtension(), YamlFile::EXTENSIONS)
        ) {
            $this->context->buildViolation($constraint::MESSAGE_NAME_INVALID)
                ->atPath('name')
                ->addViolation()
            ;
        }
    }
}
