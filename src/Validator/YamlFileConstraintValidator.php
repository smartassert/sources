<?php

declare(strict_types=1);

namespace App\Validator;

use App\Model\YamlFile;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;

class YamlFileConstraintValidator extends ConstraintValidator
{
    public function __construct(
        private Parser $yamlParser,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof YamlFileConstraint) {
            throw new UnexpectedTypeException($constraint, YamlFileConstraint::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!$value instanceof YamlFile) {
            throw new UnexpectedValueException($value, 'string');
        }

        if (false === $value->name->isValid()) {
            $this->context->buildViolation($constraint::MESSAGE_NAME_INVALID)
                ->atPath('name')
                ->addViolation()
            ;
        }

        $filename = $value->name->getValue();
        if (!(str_ends_with($filename, '.yml') || str_ends_with($filename, '.yaml'))) {
            $this->context->buildViolation($constraint::MESSAGE_NAME_INVALID)
                ->atPath('name')
                ->addViolation()
            ;
        }

        if ('' === $value->content) {
            $this->context->buildViolation($constraint::MESSAGE_CONTENT_EMPTY)
                ->atPath('content')
                ->addViolation()
            ;
        }

        try {
            $this->yamlParser->parse($value->content);
        } catch (ParseException $yamlParseException) {
            $this->context->buildViolation($constraint::MESSAGE_CONTENT_PARSE_ERROR)
                ->setParameter('{{ exception }}', $yamlParseException->getMessage())
                ->atPath('content')
                ->addViolation()
            ;
        }
    }
}
