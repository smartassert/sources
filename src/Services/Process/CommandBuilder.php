<?php

declare(strict_types=1);

namespace App\Services\Process;

class CommandBuilder
{
    /**
     * @param array<string, string> $parameters
     */
    public function build(string $command, array $parameters = []): string
    {
        $search = array_keys($parameters);
        $replace = array_values($parameters);
        array_walk($replace, function (&$parameter) {
            $parameter = sprintf("'%s'", str_replace("'", "\\'", $parameter));
        });

        return str_replace($search, $replace, $command);
    }
}
