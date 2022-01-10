<?php

declare(strict_types=1);

namespace App\Exception;

use App\Exception\FilePath\CreateException;
use App\Exception\FilePath\FilePathExceptionInterface;
use App\Exception\FilePath\NonAbsolutePathException;
use App\Exception\FilePath\NotExistsException;
use App\Exception\FilePath\OutOfScopeException;
use App\Exception\FilePath\RemoveException;
use App\Exception\FileStore\FileStoreExceptionInterface;
use App\Exception\FileStore\MirrorException;

class FileSourcePreparationException extends \Exception
{
    private const EXCEPTION_CLASS_CODE_MAP = [
        CreateException::class => 100,
        MirrorException::class => 200,
        NonAbsolutePathException::class => 300,
        NotExistsException::class => 400,
        OutOfScopeException::class => 500,
        RemoveException::class => 300,
    ];

    public function __construct(FileStoreExceptionInterface|FilePathExceptionInterface|MirrorException $previous)
    {
        $code = self::EXCEPTION_CLASS_CODE_MAP[$previous::class] ?? 0;

        parent::__construct(
            $previous->getMessage(),
            $code,
            $previous
        );
    }
}
