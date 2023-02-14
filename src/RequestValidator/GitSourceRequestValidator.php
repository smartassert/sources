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

        $hostUrl = $request->getHostUrl();
        $hostUrlLength = strlen($hostUrl);

        if (0 === $hostUrlLength) {
            throw new InvalidRequestException(
                $request,
                new InvalidField(
                    'host-url',
                    $hostUrl,
                    'This value is too short. It should have 1 character or more.',
                ),
            );
        }

        $hostUrlMaxLength = GitSource::HOST_URL_MAX_LENGTH;
        if ($hostUrlLength > $hostUrlMaxLength) {
            throw new InvalidRequestException(
                $request,
                new InvalidField(
                    'host-url',
                    $hostUrl,
                    'This value is too long. It should have ' . $hostUrlMaxLength . ' characters or less.',
                ),
            );
        }

        $path = $request->getPath();
        $pathLength = strlen($path);

        if (0 === $pathLength) {
            throw new InvalidRequestException(
                $request,
                new InvalidField(
                    'path',
                    $path,
                    'This value is too short. It should have 1 character or more.',
                ),
            );
        }

        $pathMaxLength = GitSource::PATH_MAX_LENGTH;
        if ($pathLength > $pathMaxLength) {
            throw new InvalidRequestException(
                $request,
                new InvalidField(
                    'path',
                    $path,
                    'This value is too long. It should have ' . $pathMaxLength . ' characters or less.',
                ),
            );
        }

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
