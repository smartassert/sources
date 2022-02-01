<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\RunSourcePreparationFailureReason;
use App\Enum\RunSourcePreparationState;
use App\Repository\RunSourcePreparationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RunSourcePreparationRepository::class)]
class RunSourcePreparation
{
    #[ORM\Id]
    #[ORM\OneToOne(targetEntity: RunSource::class)]
    #[ORM\JoinColumn(nullable: false)]
    private RunSource $runSource;

    #[ORM\Column(type: 'string', enumType: RunSourcePreparationState::class)]
    private RunSourcePreparationState $state;

    #[ORM\Column(type: 'string', nullable: true, enumType: RunSourcePreparationFailureReason::class)]
    private ?RunSourcePreparationFailureReason $failureReason = null;

    #[ORM\Column(type: 'text')]
    private ?string $failureMessage = null;

    public function __construct(RunSource $runSource, RunSourcePreparationState $state)
    {
        $this->runSource = $runSource;
        $this->state = $state;
    }

    public function getRunSource(): RunSource
    {
        return $this->runSource;
    }

    public function getState(): RunSourcePreparationState
    {
        return $this->state;
    }

    public function setState(RunSourcePreparationState $state): void
    {
        $this->state = $state;
    }

    public function getFailureReason(): ?RunSourcePreparationFailureReason
    {
        return $this->failureReason;
    }

    public function getFailureMessage(): ?string
    {
        return $this->failureMessage;
    }

    public function setFailed(
        RunSourcePreparationFailureReason $failureReason,
        string $failureMessage
    ): void {
        $this->failureReason = $failureReason;
        $this->failureMessage = $failureMessage;
    }
}
