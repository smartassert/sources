<?php

declare(strict_types=1);

namespace App\Exception\MessageHandler;

use App\Exception\File\WriteException;
use App\Exception\SourceRead\SourceReadExceptionInterface;
use App\Exception\UserGitRepositoryException;

class PrepareException extends \Exception
{
    public function __construct(
        private WriteException|SourceReadExceptionInterface|UserGitRepositoryException $exception
    ) {
        parent::__construct($exception->getMessage(), $exception->getCode(), $exception);
    }

    public function getHandlerException(): WriteException|SourceReadExceptionInterface|UserGitRepositoryException
    {
        return $this->exception;
    }
}
