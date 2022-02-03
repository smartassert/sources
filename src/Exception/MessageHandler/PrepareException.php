<?php

declare(strict_types=1);

namespace App\Exception\MessageHandler;

use App\Exception\DirectoryDuplicationException;
use App\Exception\UserGitRepositoryException;

class PrepareException extends \Exception
{
    public function __construct(
        private DirectoryDuplicationException|UserGitRepositoryException $exception
    ) {
        parent::__construct($exception->getMessage(), $exception->getCode(), $exception);
    }

    public function getHandlerException(): DirectoryDuplicationException|UserGitRepositoryException
    {
        return $this->exception;
    }
}
