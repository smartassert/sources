<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\MutableSerializedSuiteInterface as MutableSerializedSuite;
use App\Enum\EntityType;
use App\Enum\SerializedSuite\FailureReason;
use App\Enum\SerializedSuite\State;
use App\Model\DirectoryLocatorInterface as DirectoryLocator;
use App\Repository\SerializedSuiteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SerializedSuiteRepository::class)]
class SerializedSuite implements SerializedSuiteInterface, MutableSerializedSuite, DirectoryLocator
{
    public const ID_LENGTH = 32;

    #[ORM\ManyToOne(targetEntity: Suite::class)]
    #[ORM\JoinColumn(nullable: false)]
    private readonly Suite $suite;

    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: self::ID_LENGTH, unique: true)]
    private readonly string $id;

    /**
     * @var array<string, string>
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private array $parameters;

    #[ORM\Column(type: Types::STRING, enumType: State::class)]
    private State $state;

    #[ORM\Column(type: Types::STRING, nullable: true, enumType: FailureReason::class)]
    private ?FailureReason $failureReason = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $failureMessage = null;

    /**
     * @param non-empty-string      $id
     * @param array<string, string> $parameters
     */
    public function __construct(string $id, Suite $suite, array $parameters = [])
    {
        $this->id = $id;
        $this->suite = $suite;
        $this->parameters = $parameters;
        ksort($this->parameters);
        $this->state = State::REQUESTED;
    }

    /**
     * @return non-empty-string
     */
    public function getId(): string
    {
        \assert('' !== $this->id);

        return $this->id;
    }

    /**
     * @return array<string, string>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function setState(State $state): static
    {
        $this->state = $state;

        return $this;
    }

    public function getState(): State
    {
        return $this->state;
    }

    public function getUserId(): string
    {
        return $this->getSuite()->getUserId();
    }

    public function setPreparationFailed(FailureReason $failureReason, string $failureMessage): static
    {
        if (State::FAILED !== $this->state) {
            $this->state = State::FAILED;
            $this->failureReason = $failureReason;
            $this->failureMessage = $failureMessage;
        }

        return $this;
    }

    public function getDirectoryPath(): string
    {
        return sprintf(
            '%s/%s',
            $this->getUserId(),
            $this->getId(),
        );
    }

    public function getSuite(): Suite
    {
        return $this->suite;
    }

    public function jsonSerialize(): array
    {
        $hasEndState = State::PREPARED === $this->state || State::FAILED === $this->state;
        $isPrepared = State::PREPARED === $this->state;

        $previousStates = [];
        foreach ($this->state->getPreviousStates() as $previousState) {
            $previousStates[] = $previousState->value;
        }

        $nextStates = [];
        foreach ($this->state->getNextStates() as $nextState) {
            $nextStates[] = $nextState->value;
        }

        $data = [
            'id' => $this->getId(),
            'suite_id' => $this->getSuite()->getId(),
            'parameters' => $this->parameters,
            'state' => $this->state->value,
            'is_prepared' => $isPrepared,
            'has_end_state' => $hasEndState,
            'meta_state' => [
                'pending' => State::REQUESTED === $this->state,
                'ended' => $hasEndState,
                'succeeded' => $isPrepared,
            ],
            'previous_states' => $previousStates,
            'next_states' => $nextStates,
        ];

        if (State::FAILED === $this->state) {
            $failureReason = $this->failureReason instanceof FailureReason
                ? $this->failureReason
                : FailureReason::UNKNOWN;

            $data['failure_reason'] = $failureReason->value;
            $data['failure_message'] = (string) $this->failureMessage;
        }

        return $data;
    }

    public function getIdentifier(): EntityIdentifierInterface
    {
        return new EntityIdentifier($this->getId(), EntityType::SERIALIZED_SUITE->value);
    }
}
