<?php

declare(strict_types=1);

namespace App\Enum\SerializedSuite;

enum State: string
{
    case REQUESTED = 'requested';
    case PREPARING_RUNNING = 'preparing/running';
    case PREPARING_HALTED = 'preparing/halted';
    case FAILED = 'failed';
    case PREPARED = 'prepared';

    /**
     * @return State[]
     */
    public function getPreviousStates(): array
    {
        if (State::PREPARING_RUNNING === $this) {
            return [
                State::REQUESTED,
                State::PREPARING_HALTED,
            ];
        }

        if (State::PREPARING_HALTED === $this) {
            return [
                State::REQUESTED,
                State::PREPARING_RUNNING,
            ];
        }

        if (State::FAILED === $this || State::PREPARED === $this) {
            return [
                State::REQUESTED,
                State::PREPARING_RUNNING,
                State::PREPARING_HALTED,
            ];
        }

        return [];
    }

    /**
     * @return State[]
     */
    public function getNextStates(): array
    {
        if (State::REQUESTED === $this) {
            return [
                State::PREPARING_RUNNING,
                State::PREPARING_HALTED,
            ];
        }

        if (State::PREPARING_RUNNING === $this) {
            return [
                State::PREPARING_HALTED,
                State::FAILED,
                State::PREPARED,
            ];
        }

        if (State::PREPARING_HALTED === $this) {
            return [
                State::PREPARING_RUNNING,
                State::FAILED,
                State::PREPARED,
            ];
        }

        return [];
    }
}
