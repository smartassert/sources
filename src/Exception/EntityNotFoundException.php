<?php

declare(strict_types=1);

namespace App\Exception;

class EntityNotFoundException extends \Exception implements HasHttpErrorCodeInterface
{
    public function __construct(
        public readonly string $entityId,
        public readonly string $entityName,
    ) {
        parent::__construct(sprintf('%s "%s" not found', $this->entityName, $entityId));
    }

    public function getErrorCode(): int
    {
        return 404;
    }
}
