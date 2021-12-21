<?php

declare(strict_types=1);

namespace App\Tests\Services\Source;

use App\Repository\SourceRepository;
use Doctrine\ORM\EntityManagerInterface;

class SourceRemover
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SourceRepository $repository,
    ) {
    }

    public function removeAll(): void
    {
        $sources = $this->repository->findAll();
        foreach ($sources as $source) {
            $this->entityManager->remove($source);
        }

        $this->entityManager->flush();
    }
}
