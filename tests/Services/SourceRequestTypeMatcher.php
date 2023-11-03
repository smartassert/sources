<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Request\GitSourceRequest;

class SourceRequestTypeMatcher
{
    /**
     * @param array<mixed> $parameters
     */
    public static function matchesGitSourceRequest(array $parameters): bool
    {
        if (!array_key_exists(GitSourceRequest::PARAMETER_LABEL, $parameters)) {
            return false;
        }

        if (!array_key_exists(GitSourceRequest::PARAMETER_HOST_URL, $parameters)) {
            return false;
        }

        if (!array_key_exists(GitSourceRequest::PARAMETER_PATH, $parameters)) {
            return false;
        }

        return true;
    }
}
