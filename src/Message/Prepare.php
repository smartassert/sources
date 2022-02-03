<?php

declare(strict_types=1);

namespace App\Message;

use App\Entity\RunSource;

class Prepare
{
    /**
     * @param array<string, string> $parameters
     */
    public function __construct(
        private string $sourceId,
        private array $parameters,
    ) {
    }

    public static function createFromRunSource(RunSource $runSource): self
    {
        return new Prepare($runSource->getId(), $runSource->getParameters());
    }

    public function getSourceId(): string
    {
        return $this->sourceId;
    }

    /**
     * @return array<string, string>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }
}
