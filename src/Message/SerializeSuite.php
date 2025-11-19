<?php

declare(strict_types=1);

namespace App\Message;

use App\Entity\SerializedSuiteInterface;

class SerializeSuite
{
    /**
     * @param array<string, string> $parameters
     */
    public function __construct(
        private readonly string $suiteId,
        private readonly array $parameters,
    ) {}

    public static function createFromSerializedSuite(SerializedSuiteInterface $serializedSuite): self
    {
        return new SerializeSuite($serializedSuite->getId(), $serializedSuite->getParameters());
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
