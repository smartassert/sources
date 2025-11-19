<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\SerializedSuite;
use App\Entity\SerializedSuiteInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SerializedSuite>
 */
class SerializedSuiteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SerializedSuite::class);
    }

    public function save(SerializedSuiteInterface $entity): void
    {
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();
    }
}
