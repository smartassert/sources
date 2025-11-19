<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\SerializedSuite\FailureReason;

interface SerializedSuiteInterface extends UserHeldEntityInterface, IdentifiedEntityInterface
{
    /**
     * @return non-empty-string
     */
    public function getId(): string;

    /**
     * @return array<string, string>
     */
    public function getParameters(): array;

    public function setPreparationFailed(FailureReason $failureReason, string $failureMessage): static;

    public function getSuite(): Suite;
}
