<?php

declare(strict_types=1);

namespace App\RequestValidator;

use App\Entity\AbstractOriginSource;
use App\Exception\InvalidRequestException;
use App\Request\FileSourceRequest;
use App\ResponseBody\InvalidField;

class FileSourceRequestValidator
{
    /**
     * @throws InvalidRequestException
     */
    public function validate(FileSourceRequest $request): void
    {
        $label = $request->getLabel();
        $labelLength = mb_strlen($label);
        $minimumLabelLength = 1;
        $maximumLabelLength = AbstractOriginSource::LABEL_MAX_LENGTH;

        if ($labelLength <= $minimumLabelLength || $labelLength > $maximumLabelLength) {
            throw new InvalidRequestException(
                $request,
                new InvalidField(
                    'label',
                    $label,
                    sprintf(
                        'This value should be between %d and %d characters long.',
                        $minimumLabelLength,
                        $maximumLabelLength
                    ),
                ),
            );
        }
    }
}
