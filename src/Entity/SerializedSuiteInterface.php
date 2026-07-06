<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\SerializedSuite\FailureReason;
use App\Enum\SerializedSuite\State;

/**
 * @phpstan-type SerializedSerializedSuite array{
 *     id: string,
 *     suite_id: string,
 *     parameters: array<string, string>,
 *     state: value-of<State>,
 *     is_prepared: bool,
 *     has_end_state: bool,
 *     meta_state: array{
 *         pending: bool,
 *         ended: bool,
 *         succeeded: bool
 *     },
 *     failure_reason?: value-of<FailureReason>,
 *     failure_message?: string,
 *     previous_states: value-of<State>[],
 *     next_states: value-of<State>[]
 * }
 */
interface SerializedSuiteInterface extends UserHeldEntityInterface, IdentifiedEntityInterface, \JsonSerializable
{
    /**
     * @return non-empty-string
     */
    public function getId(): string;

    /**
     * @return array<string, string>
     */
    public function getParameters(): array;

    public function getSuite(): Suite;

    public function getDirectoryPath(): string;

    /**
     * @return SerializedSerializedSuite
     */
    public function jsonSerialize(): array;
}
