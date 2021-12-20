<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\AbstractSource;
use App\Entity\SourceInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method null|SourceInterface find($id, $lockMode = null, $lockVersion = null)
 * @method null|SourceInterface findOneBy(array $criteria, array $orderBy = null)
 * @method SourceInterface[]    findAll()
 * @method SourceInterface[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @extends ServiceEntityRepository<SourceInterface>
 */
class SourceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AbstractSource::class);
    }
}
