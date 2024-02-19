<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\SourceInterface;
use App\Entity\Suite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Parameter;
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
            ->orderBy('Suite.id', 'ASC')
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

    public function findOneBySourceOwnerAndLabel(SourceInterface $source, string $label): ?Suite
    {
        $queryBuilder = $this->createQueryBuilder('Suite');

        $labelParameter = new Parameter('Label', $label);
        $userIdParameter = new Parameter('UserId', $source->getUserId());

        $queryBuilder
            ->join('Suite.source', 'Source')
            ->where('Suite.label = :' . $labelParameter->getName())
            ->andWhere('Source.userId = :' . $userIdParameter->getName())
            ->andWhere('Suite.deletedAt IS NULL')
            ->orderBy('Suite.label', 'ASC')
            ->setMaxResults(1)
            ->setParameters(new ArrayCollection([
                new Parameter('Label', $label),
                new Parameter('UserId', $source->getUserId()),
            ]))
        ;

        $query = $queryBuilder->getQuery();
        $result = $query->getResult();

        if (is_array($result) && 1 === count($result) && isset($result[0]) && $result[0] instanceof Suite) {
            return $result[0];
        }

        return null;
    }
}
