<?php

declare(strict_types=1);

namespace App\RequestFactory;

use App\Entity\AbstractSource;
use App\Exception\InvalidRequestException;
use App\Request\FileSourceRequest;
use App\ResponseBody\InvalidField;
use Symfony\Component\HttpFoundation\Request;

class FileSourceRequestFactory
{
    /**
     * @throws InvalidRequestException
     */
    public function create(Request $request): FileSourceRequest
    {
        $label = trim((string) $request->request->get(FileSourceRequest::PARAMETER_LABEL));
        if ('' === $label || mb_strlen($label) > AbstractSource::LABEL_MAX_LENGTH) {
            $message = sprintf(
                'This value should be between 1 and %d characters long.',
                AbstractSource::LABEL_MAX_LENGTH
            );

            throw new InvalidRequestException($request, new InvalidField('label', $label, $message));
        }

        return new FileSourceRequest($label);
    }
}
