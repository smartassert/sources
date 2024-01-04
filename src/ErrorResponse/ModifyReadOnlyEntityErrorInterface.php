<?php

declare(strict_types=1);

namespace App\ErrorResponse;

interface ModifyReadOnlyEntityErrorInterface extends ErrorInterface
{
    public const ERROR_CLASS = 'modify_read_only';

    /**
     * @return array{
     *   class: 'modify_read_only',
     *   entity: array{
     *     id: non-empty-string,
     *     type: non-empty-string
     *   }
     * }
     */
    public function serialize(): array;
}
