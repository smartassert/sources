<?php

declare(strict_types=1);

namespace App\Exception;

use App\Exception\FileStore\CreateException;
use App\Exception\FileStore\FileStoreExceptionInterface;
use App\Exception\FileStore\MirrorException;
use App\Exception\FileStore\NonAbsolutePathException;
use App\Exception\FileStore\NotExistsException;
use App\Exception\FileStore\OutOfScopeException;
use App\Exception\FileStore\RemoveException;

class FileSourcePreparationException extends \Exception
{
    /**
     * @throws CreateException
     * @throws MirrorException
     * @throws NonAbsolutePathException
     * @throws NotExistsException
     * @throws OutOfScopeException
     * @throws RemoveException
     */
    private const EXCEPTION_CLASS_CODE_MAP = [
        CreateException::class => 100,
        MirrorException::class => 200,
        NonAbsolutePathException::class => 300,
        NotExistsException::class => 400,
        OutOfScopeException::class => 500,
        RemoveException::class => 300,
    ];

    public function __construct(FileStoreExceptionInterface $previous)
    {
        $code = self::EXCEPTION_CLASS_CODE_MAP[$previous::class] ?? 0;

        parent::__construct(
            $previous->getMessage(),
            $code,
            $previous
        );
    }
}
