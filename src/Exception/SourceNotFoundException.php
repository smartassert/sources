<?php

declare(strict_types=1);

namespace App\Exception;

class SourceNotFoundException extends \Exception implements HasHttpErrorCodeInterface
{
    public const MESSAGE = 'Source "%s" not found';

    public function __construct(private string $sourceId)
    {
        parent::__construct(sprintf(self::MESSAGE, $sourceId));
    }

    public function getSourceId(): string
    {
        return $this->sourceId;
    }

    public function getErrorCode(): int
    {
        return 404;
    }
}
