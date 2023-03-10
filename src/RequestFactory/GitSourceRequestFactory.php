<?php

declare(strict_types=1);

namespace App\RequestFactory;

use App\Entity\AbstractSource;
use App\Entity\GitSource;
use App\Exception\InvalidRequestException;
use App\Request\GitSourceRequest;
use App\ResponseBody\InvalidField;
use Symfony\Component\HttpFoundation\Request;

class GitSourceRequestFactory
{
    /**
     * @throws InvalidRequestException
     */
    public function create(Request $request): GitSourceRequest
    {
        $label = trim((string) $request->request->get(GitSourceRequest::PARAMETER_LABEL));
        if ('' === $label || mb_strlen($label) > AbstractSource::LABEL_MAX_LENGTH) {
            $message = sprintf(
                'This value should be between 1 and %d characters long.',
                AbstractSource::LABEL_MAX_LENGTH
            );

            throw new InvalidRequestException($request, new InvalidField('label', $label, $message));
        }

        $hostUrl = trim((string) $request->request->get(GitSourceRequest::PARAMETER_HOST_URL));
        if ('' === $hostUrl || strlen($hostUrl) > GitSource::HOST_URL_MAX_LENGTH) {
            $message = sprintf(
                'This value should be between 1 and %d characters long.',
                GitSource::HOST_URL_MAX_LENGTH
            );

            throw new InvalidRequestException($request, new InvalidField('host-url', $hostUrl, $message));
        }

        $path = trim((string) $request->request->get(GitSourceRequest::PARAMETER_PATH));
        if ('' === $path || strlen($path) > GitSource::PATH_MAX_LENGTH) {
            $message = sprintf(
                'This value should be between 1 and %d characters long.',
                GitSource::PATH_MAX_LENGTH
            );

            throw new InvalidRequestException($request, new InvalidField('path', $path, $message));
        }

        $credentials = trim((string) $request->request->get(GitSourceRequest::PARAMETER_CREDENTIALS));
        if (strlen($credentials) > GitSource::CREDENTIALS_MAX_LENGTH) {
            $message = sprintf(
                'This value should be between 0 and %d characters long.',
                GitSource::CREDENTIALS_MAX_LENGTH
            );

            throw new InvalidRequestException($request, new InvalidField('credentials', $credentials, $message));
        }

        return new GitSourceRequest($label, $hostUrl, $path, $credentials);
    }
}
