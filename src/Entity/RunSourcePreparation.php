<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\RunSource\FailureReason;
use App\Enum\RunSource\State;
use App\Repository\RunSourcePreparationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RunSourcePreparationRepository::class)]
class RunSourcePreparation
{
    #[ORM\Id]
    #[ORM\OneToOne(targetEntity: RunSource::class, cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    private RunSource $runSource;

    #[ORM\Column(type: 'string', enumType: State::class)]
    private State $state;

    #[ORM\Column(type: 'string', nullable: true, enumType: FailureReason::class)]
    private ?FailureReason $failureReason = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $failureMessage = null;

    public function __construct(RunSource $runSource)
    {
        $this->runSource = $runSource;
        $this->state = State::UNKNOWN;
    }

    public function getRunSource(): RunSource
    {
        return $this->runSource;
    }

    public function getState(): State
    {
        return $this->state;
    }

    public function setState(State $state): void
    {
        $this->state = $state;
    }

    public function getFailureReason(): ?FailureReason
    {
        return $this->failureReason;
    }

    public function getFailureMessage(): ?string
    {
        return $this->failureMessage;
    }

    public function setFailed(
        FailureReason $failureReason,
        string $failureMessage
    ): void {
        $this->failureReason = $failureReason;
        $this->failureMessage = $failureMessage;
    }
}
