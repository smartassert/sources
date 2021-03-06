<?php

declare(strict_types=1);

namespace App\Services\Source;

use App\Entity\SourceInterface;

interface TypeFinderInterface
{
    public function find(SourceInterface $source): ?SourceInterface;
}
