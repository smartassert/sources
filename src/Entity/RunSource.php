<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\RunSource\FailureReason;
use App\Enum\RunSource\State;
use App\Enum\Source\Type;
use App\Model\DirectoryLocatorInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class RunSource extends AbstractSource implements DirectoryLocatorInterface, \JsonSerializable
{
    #[ORM\ManyToOne(targetEntity: AbstractSource::class, cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: true)]
    private ?SourceOriginInterface $parent;

    /**
     * @var array<string, string>
     */
    #[ORM\Column(type: 'simple_array', nullable: true)]
    private array $parameters;

    #[ORM\Column(type: 'string', enumType: State::class)]
    private State $state;

    #[ORM\Column(type: 'string', nullable: true, enumType: FailureReason::class)]
    private ?FailureReason $failureReason = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $failureMessage = null;

    /**
     * @param array<string, string> $parameters
     */
    public function __construct(SourceOriginInterface $parent, array $parameters = [])
    {
        parent::__construct($parent->getUserId());

        $this->parent = $parent;
        $this->parameters = $parameters;
        ksort($this->parameters);
        $this->state = State::REQUESTED;
    }

    public function getParent(): ?SourceOriginInterface
    {
        return $this->parent;
    }

    public function unsetParent(): self
    {
        $this->parent = null;

        return $this;
    }

    /**
     * @return array<string, string>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getType(): Type
    {
        return Type::RUN;
    }

    public function setState(State $state): self
    {
        $this->state = $state;

        return $this;
    }

    public function getState(): State
    {
        return $this->state;
    }

    public function getFailureReason(): ?FailureReason
    {
        return $this->failureReason;
    }

    public function getFailureMessage(): ?string
    {
        return $this->failureMessage;
    }

    public function setPreparationFailed(
        FailureReason $failureReason,
        string $failureMessage
    ): self {
        $this->state = State::FAILED;
        $this->failureReason = $failureReason;
        $this->failureMessage = $failureMessage;

        return $this;
    }

    /**
     * @return array{
     *     "id": string,
     *     "user_id": string,
     *     "type": 'run',
     *     "parent": string|null,
     *     "parameters": array<string, string>,
     *     "state": string,
     *     "failure_reason"?: string,
     *     "failure_message"?: string
     * }
     */
    public function jsonSerialize(): array
    {
        $data = [
            'id' => $this->id,
            'user_id' => $this->getUserId(),
            'type' => Type::RUN->value,
            'parent' => $this->parent?->getId(),
            'parameters' => $this->parameters,
            'state' => $this->state->value,
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

    public function getDirectoryPath(): string
    {
        return sprintf(
            '%s/%s',
            $this->getUserId(),
            $this->getId(),
        );
    }
}
