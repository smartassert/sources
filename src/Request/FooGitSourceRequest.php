<?php

declare(strict_types=1);

namespace App\Request;

class FooGitSourceRequest extends AbstractSourceRequest
{
    public const PARAMETER_HOST_URL = 'host-url';
    public const PARAMETER_PATH = 'path';
    public const PARAMETER_CREDENTIALS = 'credentials';

    public function getRequiredFields(): array
    {
        $requiredFields = parent::getRequiredFields();
        $requiredFields[] = self::PARAMETER_HOST_URL;

        return $requiredFields;
    }

    public function getFields(): array
    {
        $fields = parent::getFields();
        $fields[] = self::PARAMETER_PATH;
        $fields[] = self::PARAMETER_CREDENTIALS;

        return $fields;
    }
}
