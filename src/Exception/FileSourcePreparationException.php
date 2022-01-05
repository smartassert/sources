<?php

declare(strict_types=1);

namespace App\Exception;

use App\Exception\DirectoryDuplicator\DirectoryDuplicatorExceptionInterface;
use App\Exception\DirectoryDuplicator\DuplicationException;
use App\Exception\DirectoryDuplicator\MissingSourceException;
use App\Exception\DirectoryDuplicator\TargetCreationException;
use App\Exception\DirectoryDuplicator\TargetRemovalException;

class FileSourcePreparationException extends \Exception
{
    private const EXCEPTION_CLASS_CODE_MAP = [
        DuplicationException::class => 100,
        MissingSourceException::class => 200,
        TargetCreationException::class => 300,
        TargetRemovalException::class => 400,
    ];

    public function __construct(DirectoryDuplicatorExceptionInterface $previous)
    {
        $code = self::EXCEPTION_CLASS_CODE_MAP[$previous::class] ?? 0;

        parent::__construct(
            $previous->getMessage(),
            $code,
            $previous
        );
    }
}
