<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\RunSource;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method null|RunSource find($id, $lockMode = null, $lockVersion = null)
 * @method null|RunSource findOneBy(array $criteria, array $orderBy = null)
 * @method RunSource[]    findAll()
 * @method RunSource[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @extends ServiceEntityRepository<RunSource>
 */
class RunSourceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RunSource::class);
    }
}
