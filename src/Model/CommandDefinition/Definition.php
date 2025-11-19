<?php

declare(strict_types=1);

namespace App\Model\CommandDefinition;

class Definition implements \Stringable
{
    /**
     * @var Option[]
     */
    private array $options = [];

    /**
     * @var string[]
     */
    private array $arguments = [];

    public function __construct(
        private string $command
    ) {}

    public function __toString(): string
    {
        return $this->build();
    }

    /**
     * @param Option[] $options
     */
    public function withOptions(array $options): self
    {
        $new = clone $this;
        $new->options = $options;

        return $new;
    }

    /**
     * @param array<mixed> $arguments
     */
    public function withArguments(array $arguments): self
    {
        $new = clone $this;
        $new->arguments = array_filter($arguments, function ($item) {
            return is_string($item);
        });

        return $new;
    }

    public function build(): string
    {
        $command = $this->command;

        foreach ($this->options as $option) {
            $command .= ' ' . $option;
        }

        foreach ($this->arguments as $argument) {
            $command .= ' ' . sprintf("'%s'", str_replace("'", "\\'", (string) $argument));
        }

        return $command;
    }
}
