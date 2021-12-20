<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\FileSource;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method null|FileSource find($id, $lockMode = null, $lockVersion = null)
 * @method null|FileSource findOneBy(array $criteria, array $orderBy = null)
 * @method FileSource[]    findAll()
 * @method FileSource[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @extends ServiceEntityRepository<FileSource>
 */
class FileSourceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FileSource::class);
    }
}
