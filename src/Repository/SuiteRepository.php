<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Suite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends ServiceEntityRepository<Suite>
 *
 * @method null|Suite find($id, $lockMode = null, $lockVersion = null)
 * @method null|Suite findOneBy(array $criteria, array $orderBy = null)
 * @method Suite[]    findAll()
 * @method Suite[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SuiteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Suite::class);
    }

    public function save(Suite $entity): void
    {
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();
    }

    public function delete(Suite $entity): void
    {
        $entity->setDeletedAt(new \DateTimeImmutable());

        $this->save($entity);
    }

    /**
     * @return Suite[]
     */
    public function findForUser(UserInterface $user): array
    {
        $queryBuilder = $this->createQueryBuilder('Suite');

        $queryBuilder
            ->join('Suite.source', 'Source')
            ->where('Source.userId = :UserId')
            ->andWhere('Suite.deletedAt IS NULL')
            ->orderBy('Suite.label', 'ASC')
            ->setParameter('UserId', $user->getUserIdentifier())
        ;

        $query = $queryBuilder->getQuery();

        $result = $query->getResult();
        $suites = [];

        if (is_iterable($result)) {
            foreach ($result as $suite) {
                if ($suite instanceof Suite) {
                    $suites[] = $suite;
                }
            }
        }

        return $suites;
    }
}
