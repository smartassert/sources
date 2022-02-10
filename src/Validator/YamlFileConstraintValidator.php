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

        $filename = $value->name;

        if (
            !$value->name->isValid()
            || '' === $filename->getName()
            || !in_array($filename->getExtension(), YamlFile::EXTENSIONS)
        ) {
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
