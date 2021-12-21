<?php

declare(strict_types=1);

namespace App\Services\Source;

use App\Entity\RunSource;
use App\Entity\SourceInterface;
use Doctrine\ORM\EntityManagerInterface;

class Store
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
    ) {
    }

    public function add(SourceInterface $source): void
    {
        $this->entityManager->persist($source);
        $this->entityManager->flush();
    }

    public function remove(SourceInterface $source): void
    {
        if ($source instanceof RunSource && null !== $source->getParent()) {
            $parent = $source->getParent();
            $source->unsetParent();
            $this->add($source);

            $source = $parent;
        }

        $this->entityManager->remove($source);
        $this->entityManager->flush();
    }
}
