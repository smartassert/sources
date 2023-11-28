<?php

declare(strict_types=1);

namespace App\RequestFactory;

use App\Exception\FooInvalidRequestException;
use App\FooRequest\Field\LabelField;
use App\FooResponse\SizeInterface;
use App\Request\FileSourceRequest;
use Symfony\Component\HttpFoundation\Request;

class FileSourceRequestFactory
{
    /**
     * @throws FooInvalidRequestException
     */
    public function create(Request $request): FileSourceRequest
    {
        $label = trim((string) $request->request->get(FileSourceRequest::PARAMETER_LABEL));
        $labelField = new LabelField($label);

        if ('' === $label) {
            throw new FooInvalidRequestException(
                'invalid_request_field',
                new LabelField($label),
                'empty'
            );
        }

        $sizeRequirements = $labelField->getRequirements()->getSize();
        if ($sizeRequirements instanceof SizeInterface) {
            if (mb_strlen($label) > $sizeRequirements->getMaximum()) {
                throw new FooInvalidRequestException(
                    'invalid_request_field',
                    new LabelField($label),
                    'too_large'
                );
            }
        }

        return new FileSourceRequest($label);
    }
}
