<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\AbstractSource;
use App\Entity\OriginSourceInterface;
use App\Entity\SourceInterface;
use App\Enum\Source\Type;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

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

    /**
     * @param Type[] $types
     *
     * @return OriginSourceInterface[]
     */
    public function findByUserAndType(UserInterface $user, array $types): array
    {
        $queryBuilder = $this->createQueryBuilder('Source')
            ->where('Source.userId = :UserId')
            ->andWhere(implode(' OR ', $this->createTypePredicates($types)))
            ->setParameter('UserId', $user->getUserIdentifier())
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
            return $item instanceof OriginSourceInterface;
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
     * @return string[]
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
