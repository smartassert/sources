<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\SourceInterface;
use Doctrine\ORM\EntityManagerInterface;

class SourcePersister
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
    ) {
    }

    public function persist(SourceInterface $source): void
    {
        $this->entityManager->persist($source);
        $this->entityManager->flush();
    }

    public function remove(SourceInterface $source): void
    {
        $this->entityManager->remove($source);
        $this->entityManager->flush();
    }
}
