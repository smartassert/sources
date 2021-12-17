<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Source;
use App\Entity\SourceType;
use App\Exception\InvalidSourceTypeException;
use App\Repository\SourceRepository;
use App\Repository\SourceTypeRepository;
use App\Request\CreateSourceRequest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Ulid;

class SourceFactory
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SourceRepository $repository,
        private SourceTypeRepository $sourceTypeRepository,
    ) {
    }

    /**
     * @param SourceType::TYPE_* $typeName
     *
     * @throws InvalidSourceTypeException
     */
    public function create(
        string $userId,
        string $typeName,
        string $hostUrl,
        string $path,
        ?string $accessToken
    ): Source {
        $source = $this->repository->findOneBy([
            'userId' => $userId,
            'hostUrl' => $hostUrl,
            'path' => $path,
        ]);

        if ($source instanceof Source) {
            return $source;
        }

        $type = $this->sourceTypeRepository->findOneByName($typeName);
        if (null === $type) {
            throw new InvalidSourceTypeException($typeName);
        }

        $source = new Source((string) new Ulid(), $userId, $type, $hostUrl, $path, $accessToken);

        $this->entityManager->persist($source);
        $this->entityManager->flush();

        return $source;
    }

    public function createFromRequest(UserInterface $user, CreateSourceRequest $request): Source
    {
        return $this->create(
            $user->getUserIdentifier(),
            SourceType::TYPE_GIT,
            $request->getHostUrl(),
            $request->getPath(),
            $request->getAccessToken()
        );
    }
}
