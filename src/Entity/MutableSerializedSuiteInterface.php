<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\SerializedSuite\FailureReason;
use App\Enum\SerializedSuite\State;

interface MutableSerializedSuiteInterface extends SerializedSuiteInterface
{
    public function setPreparationFailed(FailureReason $failureReason, string $failureMessage): static;

    public function setState(State $state): static;
}
