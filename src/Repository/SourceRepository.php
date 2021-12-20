<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\AbstractSource;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method null|AbstractSource find($id, $lockMode = null, $lockVersion = null)
 * @method null|AbstractSource findOneBy(array $criteria, array $orderBy = null)
 * @method AbstractSource[]    findAll()
 * @method AbstractSource[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @extends ServiceEntityRepository<AbstractSource>
 */
class SourceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AbstractSource::class);
    }
}
