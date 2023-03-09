<?php

declare(strict_types=1);

namespace App\Message;

use App\Entity\SerializedSuite;

class SerializeSuite
{
    /**
     * @param array<string, string> $parameters
     */
    public function __construct(
        private readonly string $suiteId,
        private readonly array $parameters,
    ) {
    }

    public static function createFromSerializedSuite(SerializedSuite $serializedSuite): self
    {
        return new SerializeSuite($serializedSuite->id, $serializedSuite->getParameters());
    }

    public function getSuiteId(): string
    {
        return $this->suiteId;
    }

    /**
     * @return array<string, string>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }
}
