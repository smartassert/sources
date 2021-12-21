<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Entity\SourceInterface;
use App\Services\SourcePersister;

class TestSourcePersister extends SourcePersister
{
    public function detach(SourceInterface $source): void
    {
        $this->entityManager->detach($source);
    }
}
