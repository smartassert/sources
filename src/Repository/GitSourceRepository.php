<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\GitSource;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method null|GitSource find($id, $lockMode = null, $lockVersion = null)
 * @method null|GitSource findOneBy(array $criteria, array $orderBy = null)
 * @method GitSource[]    findAll()
 * @method GitSource[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @extends ServiceEntityRepository<GitSource>
 */
class GitSourceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GitSource::class);
    }
}
