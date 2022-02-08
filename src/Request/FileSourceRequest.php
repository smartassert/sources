<?php

declare(strict_types=1);

namespace App\Request;

class FileSourceRequest extends AbstractSourceRequest
{
    public const PARAMETER_LABEL = 'label';

    public function getRequiredFields(): array
    {
        $requiredFields = parent::getRequiredFields();
        $requiredFields[] = self::PARAMETER_LABEL;

        return $requiredFields;
    }
}
