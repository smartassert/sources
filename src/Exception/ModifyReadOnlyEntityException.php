<?php

declare(strict_types=1);

namespace App\Exception;

class ModifyReadOnlyEntityException extends \Exception implements HasHttpErrorCodeInterface
{
    public function __construct(
        public readonly string $id,
        public readonly string $type,
    ) {
        parent::__construct(sprintf(
            'Cannot modify %s %s, entity is read-only',
            $this->type,
            $this->id
        ));
    }

    public function getErrorCode(): int
    {
        return 405;
    }
}
