<?php

declare(strict_types=1);

namespace App\Exception;

use App\Exception\File\CreateException;
use App\Exception\File\OutOfScopeException;
use App\Exception\File\RemoveException;
use App\Model\UserGitRepository;

class UserGitRepositoryException extends \Exception
{
    public const CODE_UNKNOWN = 50;
    public const CODE_DIRECTORY_OUT_OF_SCOPE = 100;
    public const CODE_DIRECTORY_REMOVAL_FAILED = 200;
    public const CODE_DIRECTORY_CREATION_FAILED = 300;
    public const CODE_GIT_CLONE_FAILED = 400;
    public const CODE_GIT_CHECKOUT_FAILED = 500;

    public function __construct(
        private UserGitRepository $userGitRepository,
        \Throwable $previous
    ) {
        $message = 'Unknown';
        $code = self::CODE_UNKNOWN;

        if ($previous instanceof OutOfScopeException) {
            $message = $previous->getMessage();
            $code = self::CODE_DIRECTORY_OUT_OF_SCOPE;
        }

        if ($previous instanceof RemoveException) {
            $message = $previous->getMessage();
            $code = self::CODE_DIRECTORY_REMOVAL_FAILED;
        }

        if ($previous instanceof CreateException) {
            $message = $previous->getMessage();
            $code = self::CODE_DIRECTORY_CREATION_FAILED;
        }

        if ($previous instanceof GitActionException) {
            $message = $previous->getMessage();

            if (GitActionException::ACTION_CLONE === $previous->getAction()) {
                $code = self::CODE_GIT_CLONE_FAILED;
            }

            if (GitActionException::ACTION_CHECKOUT === $previous->getAction()) {
                $code = self::CODE_GIT_CHECKOUT_FAILED;
            }
        }

        parent::__construct($message, $code, $previous);
    }

    public function getUserGitRepository(): UserGitRepository
    {
        return $this->userGitRepository;
    }
}
