<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\SourceType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method null|SourceType find($id, $lockMode = null, $lockVersion = null)
 * @method null|SourceType findOneBy(array $criteria, array $orderBy = null)
 * @method SourceType[]    findAll()
 * @method SourceType[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @extends ServiceEntityRepository<SourceType>
 */
class SourceTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SourceType::class);
    }

    /**
     * @param SourceType::TYPE_* $name
     */
    public function findOneByName(string $name): ?SourceType
    {
        return $this->findOneBy([
            'name' => $name,
        ]);
    }
}
