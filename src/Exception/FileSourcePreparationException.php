<?php

declare(strict_types=1);

namespace App\Exception;

use App\Exception\File\CreateException;
use App\Exception\File\MirrorException;
use App\Exception\File\MutationExceptionInterface;
use App\Exception\File\NonAbsolutePathException;
use App\Exception\File\NotExistsException;
use App\Exception\File\OutOfScopeException;
use App\Exception\File\PathExceptionInterface;
use App\Exception\File\RemoveException;

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

    public function __construct(MutationExceptionInterface|PathExceptionInterface|MirrorException $previous)
    {
        $code = self::EXCEPTION_CLASS_CODE_MAP[$previous::class] ?? 0;

        parent::__construct(
            $previous->getMessage(),
            $code,
            $previous
        );
    }
}
