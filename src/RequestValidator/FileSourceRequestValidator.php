<?php

declare(strict_types=1);

namespace App\RequestValidator;

use App\Entity\AbstractOriginSource;
use App\Exception\InvalidRequestException;
use App\Request\FileSourceRequest;

class FileSourceRequestValidator
{
    public function __construct(
        private readonly ValueLengthValidator $valueLengthValidator,
    ) {
    }

    /**
     * @throws InvalidRequestException
     */
    public function validate(FileSourceRequest $request): void
    {
        $this->valueLengthValidator->validate(
            $request,
            'label',
            $request->getLabel(),
            1,
            AbstractOriginSource::LABEL_MAX_LENGTH
        );
    }
}
