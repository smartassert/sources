<?php

declare(strict_types=1);

namespace App\RequestFactory;

use App\Exception\FooInvalidRequestException;
use App\FooRequest\Field\LabelField;
use App\FooRequest\FieldValidator;
use App\Request\FileSourceRequest;
use Symfony\Component\HttpFoundation\Request;

readonly class FileSourceRequestFactory
{
    public function __construct(
        private FieldValidator $fieldValidator,
    ) {
    }

    /**
     * @throws FooInvalidRequestException
     */
    public function create(Request $request): FileSourceRequest
    {
        $labelField = new LabelField(
            trim($request->request->getString(FileSourceRequest::PARAMETER_LABEL))
        );

        $this->fieldValidator->validate($labelField);
        \assert('' !== $labelField->getValue());

        return new FileSourceRequest($labelField->getValue());
    }
}
