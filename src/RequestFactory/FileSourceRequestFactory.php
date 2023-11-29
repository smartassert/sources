<?php

declare(strict_types=1);

namespace App\RequestFactory;

use App\Entity\AbstractSource;
use App\Exception\FooInvalidRequestException;
use App\FooRequest\Field\StringField;
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
        $label = $this->fieldValidator->validateNonEmptyString(new StringField(
            FileSourceRequest::PARAMETER_LABEL,
            trim($request->request->getString(FileSourceRequest::PARAMETER_LABEL)),
            1,
            AbstractSource::LABEL_MAX_LENGTH,
        ));

        return new FileSourceRequest($label);
    }
}
