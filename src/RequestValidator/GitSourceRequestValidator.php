<?php

declare(strict_types=1);

namespace App\RequestValidator;

use App\Entity\AbstractOriginSource;
use App\Entity\GitSource;
use App\Exception\InvalidRequestException;
use App\Request\GitSourceRequest;
use App\ResponseBody\InvalidField;

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
            $request->getLabel(),
            1,
            AbstractOriginSource::LABEL_MAX_LENGTH
        );

        $this->valueLengthValidator->validate(
            $request,
            'host-url',
            $request->getHostUrl(),
            1,
            GitSource::HOST_URL_MAX_LENGTH
        );

        $this->valueLengthValidator->validate(
            $request,
            'path',
            $request->getPath(),
            1,
            GitSource::PATH_MAX_LENGTH
        );

        $credentials = $request->getCredentials();
        $credentialsLength = strlen($credentials);
        $credentialsMaxLength = GitSource::CREDENTIALS_MAX_LENGTH;

        if ($credentialsLength > $credentialsMaxLength) {
            throw new InvalidRequestException(
                $request,
                new InvalidField(
                    'credentials',
                    $credentials,
                    'This value is too long. It should have ' . $credentialsMaxLength . ' characters or less.',
                ),
            );
        }
    }
}
