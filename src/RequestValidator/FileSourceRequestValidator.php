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

        if (0 === $labelLength) {
            throw new InvalidRequestException(
                $request,
                new InvalidField(
                    'label',
                    '',
                    'This value is too short. It should have 1 character or more.',
                ),
            );
        }

        $maxLabelLength = AbstractOriginSource::LABEL_MAX_LENGTH;
        if ($labelLength > $maxLabelLength) {
            throw new InvalidRequestException(
                $request,
                new InvalidField(
                    'label',
                    $label,
                    'This value is too long. It should have ' . $maxLabelLength . ' characters or less.',
                ),
            );
        }
    }
}
