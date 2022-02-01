<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\RunSourcePreparation;
use Doctrine\ORM\EntityManagerInterface;

class RunSourcePreparationStore
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
    ) {
    }

    public function add(RunSourcePreparation $preparation): void
    {
        $this->entityManager->persist($preparation);
        $this->entityManager->flush();
    }
}
