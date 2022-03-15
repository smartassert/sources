<?php

declare(strict_types=1);

namespace App\Validator;

use SmartAssert\YamlFile\Validation\ContentContext;
use SmartAssert\YamlFile\Validation\YamlFileContext;
use SmartAssert\YamlFile\Validator\YamlFileValidator;
use SmartAssert\YamlFile\YamlFile;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class YamlFileConstraintValidator extends ConstraintValidator
{
    public function __construct(
        private YamlFileValidator $validator,
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
            throw new UnexpectedValueException($value, YamlFile::class);
        }

        $yamlFileValidation = $this->validator->validate($value);
        if (false === $yamlFileValidation->isValid()) {
            if (YamlFileContext::CONTENT === $yamlFileValidation->getContext()) {
                $previousContext = $yamlFileValidation->getPrevious()?->getContext();

                if (ContentContext::NOT_EMPTY === $previousContext) {
                    $this->context->buildViolation($constraint::MESSAGE_CONTENT_EMPTY)
                        ->atPath('content')
                        ->addViolation()
                    ;
                }

                if (ContentContext::IS_YAML === $previousContext) {
                    $this->context->buildViolation($constraint::MESSAGE_CONTENT_PARSE_ERROR)
                        ->setParameter(
                            '{{ exception }}',
                            (string) $yamlFileValidation->getPrevious()?->getErrorMessage()
                        )
                        ->atPath('content')
                        ->addViolation()
                    ;
                }
            }
        }
    }
}
