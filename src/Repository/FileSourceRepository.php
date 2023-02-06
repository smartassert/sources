<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\FileSource;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

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

    public function findOneFileSourceByUserAndLabel(UserInterface $user, string $label): ?FileSource
    {
        $queryBuilder = $this->createQueryBuilder('FileSource')
            ->where('FileSource.userId = :UserId')
            ->andWhere('FileSource.label = :Label')
            ->setParameter('UserId', $user->getUserIdentifier())
            ->setParameter('Label', $label)
            ->setMaxResults(1)
        ;

        $query = $queryBuilder->getQuery();

        try {
            $fileSource = $query->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }

        return $fileSource instanceof FileSource ? $fileSource : null;
    }
}
