<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\RunSource;
use App\Entity\RunSourcePreparation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method null|RunSourcePreparation find($id, $lockMode = null, $lockVersion = null)
 * @method null|RunSourcePreparation findOneBy(array $criteria, array $orderBy = null)
 * @method RunSourcePreparation[]    findAll()
 * @method RunSourcePreparation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @extends ServiceEntityRepository<RunSourcePreparation>
 */
class RunSourcePreparationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RunSourcePreparation::class);
    }

    public function findByRunSource(RunSource $runSource): ?RunSourcePreparation
    {
        return $this->findOneBy([
            'runSource' => $runSource,
        ]);
    }
}
