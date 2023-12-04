<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Entity\AbstractSource;
use App\Exception\BadRequestException;
use App\Request\FileSourceRequest;
use App\RequestField\Field\StringField;
use App\RequestField\Validator\StringFieldValidator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

readonly class FileSourceRequestResolver implements ValueResolverInterface
{
    public function __construct(
        private StringFieldValidator $fieldValidator,
    ) {
    }

    /**
     * @return FileSourceRequest[]
     *
     * @throws BadRequestException
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (FileSourceRequest::class !== $argument->getType()) {
            return [];
        }

        $label = $this->fieldValidator->validateNonEmptyString(new StringField(
            FileSourceRequest::PARAMETER_LABEL,
            trim($request->request->getString(FileSourceRequest::PARAMETER_LABEL)),
            1,
            AbstractSource::LABEL_MAX_LENGTH,
        ));

        return [new FileSourceRequest($label)];
    }
}
