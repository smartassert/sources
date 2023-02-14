<?php

declare(strict_types=1);

namespace App\RequestValidator;

use App\Entity\AbstractOriginSource;
use App\Entity\GitSource;
use App\Exception\InvalidRequestException;
use App\Request\GitSourceRequest;

class GitSourceRequestValidator
{
    public function __construct(
        private readonly ValueLengthValidator $valueLengthValidator,
    ) {
    }

    /**
     * @throws InvalidRequestException
     */
    public function validate(GitSourceRequest $request): void
    {
        $this->valueLengthValidator->validate(
            $request,
            'label',
            $request->label,
            1,
            AbstractOriginSource::LABEL_MAX_LENGTH
        );

        $this->valueLengthValidator->validate(
            $request,
            'host-url',
            $request->hostUrl,
            1,
            GitSource::HOST_URL_MAX_LENGTH
        );

        $this->valueLengthValidator->validate(
            $request,
            'path',
            $request->path,
            1,
            GitSource::PATH_MAX_LENGTH
        );

        $this->valueLengthValidator->validate(
            $request,
            'credentials',
            $request->credentials,
            0,
            GitSource::CREDENTIALS_MAX_LENGTH
        );
    }
}
