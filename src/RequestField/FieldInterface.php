<?php

declare(strict_types=1);

namespace App\RequestField;

/**
 * @phpstan-type SerializedField array{
 *   name: non-empty-string,
 *   value: scalar|array<scalar>,
 *   requirements?: array{
 *     data_type: string,
 *     size?: array{
 *       minimum: int,
 *       maximum: ?int
 *     }
 *   }
 * }
 */
interface FieldInterface
{
    /**
     * @return non-empty-string
     */
    public function getName(): string;

    /**
     * @return array<scalar>|scalar
     */
    public function getValue(): mixed;

    public function getRequirements(): ?RequirementsInterface;

    public function getErrorPosition(): ?int;

    public function withErrorPosition(int $position): FieldInterface;

    /**
     * @return SerializedField
     */
    public function jsonSerialize(): array;
}
