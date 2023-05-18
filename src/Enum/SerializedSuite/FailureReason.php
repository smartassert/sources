<?php

declare(strict_types=1);

namespace App\Enum\SerializedSuite;

enum FailureReason: string
{
    case UNKNOWN = 'unknown';
    case GIT_REPOSITORY_OUT_OF_SCOPE = 'local-git-repository/out-of-scope';
    case GIT_CLONE = 'git/clone';
    case GIT_CHECKOUT = 'git/checkout';
    case GIT_UNKNOWN = 'git/unknown';
    case UNSERIALIZABLE_SOURCE_TYPE = 'source/unserializable-type';
    case UNABLE_TO_WRITE_TO_TARGET = 'target/write';
    case UNABLE_TO_READ_FROM_SOURCE_REPOSITORY = 'source-repository/read';
}
