<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Entity\AbstractSource;
use App\Exception\ErrorResponseException;
use App\Request\FileSourceRequest;
use App\RequestParameter\Factory;
use App\RequestParameter\Validator\StringFieldValidator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

readonly class FileSourceRequestResolver implements ValueResolverInterface
{
    public function __construct(
        private StringFieldValidator $fieldValidator,
        private Factory $fieldFactory,
    ) {
    }

    /**
     * @return FileSourceRequest[]
     *
     * @throws ErrorResponseException
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (FileSourceRequest::class !== $argument->getType()) {
            return [];
        }

        $label = $this->fieldValidator->validateNonEmptyString($this->fieldFactory->createStringParameter(
            FileSourceRequest::PARAMETER_LABEL,
            trim($request->request->getString(FileSourceRequest::PARAMETER_LABEL)),
            1,
            AbstractSource::LABEL_MAX_LENGTH,
        ));

        return [new FileSourceRequest($label)];
    }
}
