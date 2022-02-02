<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Repository\SourceRepository;
use Doctrine\ORM\EntityManagerInterface;

class EntityRemover
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SourceRepository $sourceRepository,
    ) {
    }

    public function removeAll(): void
    {
        foreach ($this->sourceRepository->findAll() as $entity) {
            $this->entityManager->remove($entity);
        }

        $this->entityManager->flush();
    }
}
