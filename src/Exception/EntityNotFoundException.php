<?php

declare(strict_types=1);

namespace App\Exception;

use App\ErrorResponse\HasHttpStatusCodeInterface;

class EntityNotFoundException extends \Exception implements HasHttpStatusCodeInterface
{
    public function __construct(
        public readonly string $entityId,
        public readonly string $entityName,
    ) {
        parent::__construct(sprintf('%s "%s" not found', $this->entityName, $entityId));
    }

    public function getStatusCode(): int
    {
        return 404;
    }
}
