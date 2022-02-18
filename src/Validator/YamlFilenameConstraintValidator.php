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

        $filename = $value->getValue();
        if (
            '' === $filename
            || str_contains($filename, '\\')
            || str_contains($filename, chr(0))
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