<?php

declare(strict_types=1);

namespace App\Enum\SerializedSuite;

enum FailureReason: string
{
    case UNKNOWN = 'unknown';
    case TARGET_CREATE = 'target/create';
    case TARGET_REMOVE = 'target/remove';
    case TARGET_OUT_OF_SCOPE = 'target/out-of-scope';
    case MIRROR = 'mirror';
    case SOURCE_NOT_EXISTS = 'source/not-exists';
    case GIT_REPOSITORY_OUT_OF_SCOPE = 'local-git-repository/out-of-scope';
    case GIT_REPOSITORY_CREATE = 'local-git-repository/create';
    case GIT_CLONE = 'git/clone';
    case GIT_CHECKOUT = 'git/checkout';
}
