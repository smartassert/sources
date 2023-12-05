<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Entity\AbstractSource;
use App\Entity\GitSource;
use App\Exception\BadRequestException;
use App\Request\GitSourceRequest;
use App\RequestField\Field\Factory;
use App\RequestField\Validator\StringFieldValidator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

readonly class GitSourceRequestResolver implements ValueResolverInterface
{
    public function __construct(
        private StringFieldValidator $fieldValidator,
        private Factory $fieldFactory,
    ) {
    }

    /**
     * @return GitSourceRequest[]
     *
     * @throws BadRequestException
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (GitSourceRequest::class !== $argument->getType()) {
            return [];
        }

        $label = $this->fieldValidator->validateNonEmptyString($this->fieldFactory->createStringField(
            GitSourceRequest::PARAMETER_LABEL,
            trim($request->request->getString(GitSourceRequest::PARAMETER_LABEL)),
            1,
            AbstractSource::LABEL_MAX_LENGTH,
        ));

        $hostUrl = $this->fieldValidator->validateNonEmptyString($this->fieldFactory->createStringField(
            GitSourceRequest::PARAMETER_HOST_URL,
            trim($request->request->getString(GitSourceRequest::PARAMETER_HOST_URL)),
            1,
            GitSource::HOST_URL_MAX_LENGTH,
        ));

        $path = $this->fieldValidator->validateNonEmptyString($this->fieldFactory->createStringField(
            GitSourceRequest::PARAMETER_PATH,
            trim($request->request->getString(GitSourceRequest::PARAMETER_PATH)),
            1,
            GitSource::PATH_MAX_LENGTH,
        ));

        $credentials = $this->fieldValidator->validateString($this->fieldFactory->createStringField(
            GitSourceRequest::PARAMETER_CREDENTIALS,
            trim($request->request->getString(GitSourceRequest::PARAMETER_CREDENTIALS)),
            0,
            GitSource::CREDENTIALS_MAX_LENGTH,
        ));

        return [new GitSourceRequest($label, $hostUrl, $path, $credentials)];
    }
}
