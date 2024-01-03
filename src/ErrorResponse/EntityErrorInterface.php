<?php

declare(strict_types=1);

namespace App\ErrorResponse;

use App\Entity\IdentifyingEntityInterface;

/**
 * @phpstan-type SerializedModifyReadOnlyEntityError array{
 *   class: non-empty-string,
 *   entity: array{
 *     id: non-empty-string,
 *     type: non-empty-string
 *   }
 * }
 */
interface EntityErrorInterface extends ErrorInterface
{
    public function getEntity(): IdentifyingEntityInterface;

    /**
     * @return SerializedModifyReadOnlyEntityError
     */
    public function serialize(): array;
}
