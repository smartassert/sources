<?php

declare(strict_types=1);

namespace App\Model;

class CommandDefinition implements \Stringable
{
    /**
     * @param array<string, string> $parameters
     */
    public function __construct(
        private string $command,
        private array $parameters = []
    ) {
    }

    public function __toString(): string
    {
        return $this->build();
    }

    public function build(): string
    {
        $search = array_keys($this->parameters);
        $replace = array_values($this->parameters);
        array_walk($replace, function (&$parameter) {
            $parameter = sprintf("'%s'", str_replace("'", "\\'", $parameter));
        });

        return str_replace($search, $replace, $this->command);
    }
}
