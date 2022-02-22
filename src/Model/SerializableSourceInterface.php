<?php

declare(strict_types=1);

namespace App\Model;

interface SerializableSourceInterface extends \Stringable
{
    public function __toString(): string;

    public function getSerializableSourcePath(): string;
}
