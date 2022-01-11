<?php

declare(strict_types=1);

namespace App\Exception;

use App\Exception\File\CreateException;
use App\Exception\File\FileExceptionInterface;
use App\Exception\File\MirrorException;
use App\Exception\File\NotExistsException;
use App\Exception\File\OutOfScopeException;
use App\Exception\File\RemoveException;

class FileSourcePreparationException extends \Exception
{
    private const EXCEPTION_CLASS_CODE_MAP = [
        CreateException::class => 100,
        MirrorException::class => 200,
        NotExistsException::class => 300,
        OutOfScopeException::class => 400,
        RemoveException::class => 500,
    ];

    public function __construct(FileExceptionInterface $previous)
    {
        $code = self::EXCEPTION_CLASS_CODE_MAP[$previous::class] ?? 0;

        parent::__construct(
            $previous->getMessage(),
            $code,
            $previous
        );
    }
}
