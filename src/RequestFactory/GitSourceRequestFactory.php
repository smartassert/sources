<?php

declare(strict_types=1);

namespace App\RequestFactory;

use App\Entity\AbstractSource;
use App\Entity\GitSource;
use App\Exception\FooInvalidRequestException;
use App\FooRequest\Field\StringField;
use App\FooRequest\FieldValidator;
use App\Request\GitSourceRequest;
use Symfony\Component\HttpFoundation\Request;

readonly class GitSourceRequestFactory
{
    public function __construct(
        private FieldValidator $fieldValidator,
    ) {
    }

    /**
     * @throws FooInvalidRequestException
     */
    public function create(Request $request): GitSourceRequest
    {
        $label = new StringField(
            GitSourceRequest::PARAMETER_LABEL,
            trim($request->request->getString(GitSourceRequest::PARAMETER_LABEL)),
            1,
            AbstractSource::LABEL_MAX_LENGTH,
        );
        $this->fieldValidator->validate($label);

        $hostUrl = new StringField(
            GitSourceRequest::PARAMETER_HOST_URL,
            trim($request->request->getString(GitSourceRequest::PARAMETER_HOST_URL)),
            1,
            GitSource::HOST_URL_MAX_LENGTH,
        );
        $this->fieldValidator->validate($hostUrl);

        $path = new StringField(
            GitSourceRequest::PARAMETER_PATH,
            trim($request->request->getString(GitSourceRequest::PARAMETER_PATH)),
            1,
            GitSource::PATH_MAX_LENGTH,
        );
        $this->fieldValidator->validate($path);

        $credentials = new StringField(
            GitSourceRequest::PARAMETER_CREDENTIALS,
            trim($request->request->getString(GitSourceRequest::PARAMETER_CREDENTIALS)),
            0,
            GitSource::CREDENTIALS_MAX_LENGTH,
        );

        $this->fieldValidator->validate($credentials);

        \assert('' !== (string) $label);
        \assert('' !== (string) $hostUrl);
        \assert('' !== (string) $path);
        \assert('' !== (string) $credentials);

        return new GitSourceRequest((string) $label, (string) $hostUrl, (string) $path, (string) $credentials);
    }
}
