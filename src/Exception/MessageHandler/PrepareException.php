<?php

declare(strict_types=1);

namespace App\Exception\MessageHandler;

use App\Exception\File\WriteException;
use App\Exception\GitRepositoryException;
use App\Exception\SourceRead\SourceReadExceptionInterface;

class PrepareException extends \Exception
{
    public function __construct(
        private WriteException|SourceReadExceptionInterface|GitRepositoryException $exception
    ) {
        parent::__construct($exception->getMessage(), $exception->getCode(), $exception);
    }

    public function getHandlerException(): WriteException|SourceReadExceptionInterface|GitRepositoryException
    {
        return $this->exception;
    }
}
