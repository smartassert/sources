<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Entity\AbstractSource;
use App\Entity\GitSource;
use App\Exception\FooInvalidRequestException;
use App\FooRequest\Field\StringField;
use App\FooRequest\StringFieldValidator;
use App\Request\GitSourceRequest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

readonly class GitSourceRequestResolver implements ValueResolverInterface
{
    public function __construct(
        private StringFieldValidator $fieldValidator,
    ) {
    }

    /**
     * @return GitSourceRequest[]
     *
     * @throws FooInvalidRequestException
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (GitSourceRequest::class !== $argument->getType()) {
            return [];
        }

        $label = $this->fieldValidator->validateNonEmptyString(new StringField(
            GitSourceRequest::PARAMETER_LABEL,
            trim($request->request->getString(GitSourceRequest::PARAMETER_LABEL)),
            1,
            AbstractSource::LABEL_MAX_LENGTH,
        ));

        $hostUrl = $this->fieldValidator->validateNonEmptyString(new StringField(
            GitSourceRequest::PARAMETER_HOST_URL,
            trim($request->request->getString(GitSourceRequest::PARAMETER_HOST_URL)),
            1,
            GitSource::HOST_URL_MAX_LENGTH,
        ));

        $path = $this->fieldValidator->validateNonEmptyString(new StringField(
            GitSourceRequest::PARAMETER_PATH,
            trim($request->request->getString(GitSourceRequest::PARAMETER_PATH)),
            1,
            GitSource::PATH_MAX_LENGTH,
        ));

        $credentials = $this->fieldValidator->validateString(new StringField(
            GitSourceRequest::PARAMETER_CREDENTIALS,
            trim($request->request->getString(GitSourceRequest::PARAMETER_CREDENTIALS)),
            0,
            GitSource::CREDENTIALS_MAX_LENGTH,
        ));

        return [new GitSourceRequest($label, $hostUrl, $path, $credentials)];
    }
}
