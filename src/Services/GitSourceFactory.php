<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\GitSource;
use App\Entity\SourceType;
use App\Exception\InvalidSourceTypeException;
use App\Repository\GitSourceRepository;
use App\Repository\SourceTypeRepository;
use App\Request\CreateSourceRequest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Ulid;

class GitSourceFactory
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private GitSourceRepository $repository,
        private SourceTypeRepository $sourceTypeRepository,
    ) {
    }

    /**
     * @throws InvalidSourceTypeException
     */
    public function create(
        string $userId,
        string $hostUrl,
        string $path,
        ?string $accessToken
    ): GitSource {
        $source = $this->repository->findOneBy([
            'userId' => $userId,
            'hostUrl' => $hostUrl,
            'path' => $path,
        ]);

        if ($source instanceof GitSource) {
            return $source;
        }

        $type = $this->sourceTypeRepository->findOneByName(SourceType::TYPE_GIT);
        if (null === $type) {
            throw new InvalidSourceTypeException(SourceType::TYPE_GIT);
        }

        $source = new GitSource((string) new Ulid(), $userId, $type, $hostUrl, $path, $accessToken);

        $this->entityManager->persist($source);
        $this->entityManager->flush();

        return $source;
    }

    /**
     * @throws InvalidSourceTypeException
     */
    public function createFromRequest(UserInterface $user, CreateSourceRequest $request): GitSource
    {
        return $this->create(
            $user->getUserIdentifier(),
            $request->getHostUrl(),
            $request->getPath(),
            $request->getAccessToken()
        );
    }
}
