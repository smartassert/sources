<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\AbstractSource;
use App\Entity\SourceInterface;
use App\Enum\Source\Type;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends ServiceEntityRepository<SourceInterface>
 */
class SourceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AbstractSource::class);
    }

    public function save(SourceInterface $entity): void
    {
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();
    }

    public function delete(SourceInterface $source): void
    {
        $source->setDeletedAt(new \DateTimeImmutable());

        $this->save($source);
    }

    /**
     * @param Type[] $types
     *
     * @return SourceInterface[]
     */
    public function findNonDeletedByUserAndType(UserInterface $user, array $types): array
    {
        $queryBuilder = $this->createQueryBuilder('Source')
            ->where('Source.userId = :UserId')
            ->andWhere(implode(' OR ', $this->createTypePredicates($types)))
            ->andWhere('Source.deletedAt IS NULL')
            ->setParameter('UserId', $user->getUserIdentifier())
            ->orderBy('Source.id', 'ASC')
        ;

        $typeParameters = $this->createTypeParameters($types);
        foreach ($typeParameters as $key => $value) {
            $queryBuilder->setParameter($key, $value);
        }

        $query = $queryBuilder->getQuery();

        $result = $query->execute();
        if (!is_array($result)) {
            return [];
        }

        return array_filter($result, function ($item) {
            return $item instanceof SourceInterface;
        });
    }

    /**
     * @param Type[] $types
     *
     * @return string[]
     */
    private function createTypePredicates(array $types): array
    {
        $predicates = [];
        foreach ($types as $typeIndex => $type) {
            $predicates[] = 'Source INSTANCE OF :Type' . $typeIndex;
        }

        return $predicates;
    }

    /**
     * @param Type[] $types
     *
     * @return array<value-of<Type>>
     */
    private function createTypeParameters(array $types): array
    {
        $parameters = [];
        foreach ($types as $typeIndex => $type) {
            $parameters[':Type' . $typeIndex] = $type->value;
        }

        return $parameters;
    }
}
